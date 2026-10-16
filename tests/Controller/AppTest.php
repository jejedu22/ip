<?php

namespace App\Tests\Controller;

use App\Entity\AdminUser;
use App\Entity\TransmissionLog;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

class AppTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($this->em);
        $meta = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($meta);
        $tool->createSchema($meta);
    }

    private function filledForm(array $extra = []): Form
    {
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Envoyer la fiche à la CRIP')->form();
        $values = array_merge([
            'transmission[recueil][date]' => '2026-10-01',
            'transmission[service][nom]' => 'Dupont',
            'transmission[service][prenom]' => 'Alice',
            'transmission[service][fonction]' => 'Assistante sociale',
            'transmission[service][organisme]' => 'CMS de Lannion',
            'transmission[service][courriel]' => 'alice.dupont@example.org',
            'transmission[informateur][nom]' => 'Informateur-Secret',
            'transmission[mineurs][0][nom]' => 'Enfantnom',
            'transmission[mineurs][0][prenom]' => 'Enfantprenom',
            'transmission[adresse_mineurs]' => '1 rue des Lilas',
            'transmission[code_postal]' => '22300',
            'transmission[commune]' => 'Lannion',
            'transmission[elements]' => 'Faits très confidentiels XYZ.',
        ], $extra);
        $form->setValues($values);
        $form['transmission[recueil][mode]']->select('Téléphone');
        $form['transmission[parents_informes]']->select('Non');

        return $form;
    }

    public function testFormIsDisplayed(): void
    {
        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Fiche de transmission d’une information préoccupante');
        $this->assertResponseHeaderSame('X-Frame-Options', 'DENY');
    }

    public function testValidSubmissionSendsMailAndStoresOnlySenderAndDate(): void
    {
        $this->client->submit($this->filledForm());
        $this->assertResponseRedirects('/merci');
        $this->assertEmailCount(1);

        $mail = $this->getMailerMessage();
        $this->assertEmailAddressContains($mail, 'to', 'crip22@example.org');
        $this->assertEmailAddressContains($mail, 'reply-to', 'alice.dupont@example.org');
        $this->assertEmailHtmlBodyContains($mail, 'Faits très confidentiels XYZ.');
        $this->assertEmailHtmlBodyContains($mail, 'Enfantnom');
        $this->assertEmailTextBodyContains($mail, 'Enfantnom');
        $this->assertStringNotContainsString('Enfantnom', (string) $mail->getSubject());

        // Seuls l'expéditeur et la date sont conservés.
        $logs = $this->em->getRepository(TransmissionLog::class)->findAll();
        $this->assertCount(1, $logs);
        $this->assertSame('Alice Dupont', $logs[0]->getSenderName());
        $this->assertSame('alice.dupont@example.org', $logs[0]->getSenderEmail());

        $conn = $this->em->getConnection();
        $this->assertEqualsCanonicalizing(['id', 'sender_name', 'sender_email', 'sent_at'], array_keys($conn->createSchemaManager()->listTableColumns('transmission_log')));
        $dump = '';
        foreach ($conn->createSchemaManager()->listTableNames() as $table) {
            $dump .= json_encode($conn->fetchAllAssociative("SELECT * FROM \"$table\""));
        }
        foreach (['Enfantnom', 'Enfantprenom', 'Faits', 'Lilas', 'Lannion', 'Informateur-Secret'] as $secret) {
            $this->assertStringNotContainsString($secret, $dump);
        }
    }

    public function testAnonymityDropsInformantIdentity(): void
    {
        $form = $this->filledForm();
        $form['transmission[informateur][anonyme]']->tick();
        $this->client->submit($form);
        $this->assertResponseRedirects('/merci');
        $mail = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($mail, 'anonymat est sollicité');
        $this->assertEmailHtmlBodyNotContains($mail, 'Informateur-Secret');
    }

    public function testInvalidSubmissionIsRejected(): void
    {
        $this->client->submit($this->filledForm(['transmission[elements]' => '', 'transmission[service][courriel]' => 'pas-un-mail']));
        $this->assertResponseStatusCodeSame(422);
        $this->assertEmailCount(0);
        $this->assertCount(0, $this->em->getRepository(TransmissionLog::class)->findAll());
    }

    public function testHoneypotSendsNothing(): void
    {
        $this->client->submit($this->filledForm(['transmission[website]' => 'http://spam']));
        $this->assertResponseRedirects('/merci');
        $this->assertEmailCount(0);
        $this->assertCount(0, $this->em->getRepository(TransmissionLog::class)->findAll());
    }

    public function testAdminIsProtected(): void
    {
        foreach (['/admin', '/admin/parametres', '/admin/mot-de-passe'] as $url) {
            $this->client->request('GET', $url);
            $this->assertResponseRedirects('/admin/login');
        }
    }

    public function testAdminCanChangeRecipient(): void
    {
        $admin = (new AdminUser())->setEmail('admin@example.org')->setPassword('x');
        $this->em->persist($admin);
        $this->em->flush();
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/parametres');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Enregistrer')->form(['settings[recipient]' => 'nouvelle.adresse@example.org']);
        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/parametres');

        $this->client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.stat--wide', 'nouvelle.adresse@example.org');

        $this->client->submit($this->filledForm());
        $this->assertEmailAddressContains($this->getMailerMessage(), 'to', 'nouvelle.adresse@example.org');

        $this->client->request('GET', '/admin');
        $this->assertSelectorTextContains('.table', 'alice.dupont@example.org');
    }

    public function testAdminLoginRejectsBadCredentials(): void
    {
        $crawler = $this->client->request('GET', '/admin/login');
        $this->assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Se connecter')->form(['_username' => 'x@example.org', '_password' => 'mauvais']));
        $this->assertResponseRedirects('/admin/login');
    }
}
