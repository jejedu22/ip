# Fiche de transmission d'une information préoccupante (CRIP 22)

Application Symfony 7.4 reprenant le formulaire PDF
[« Fiche de transmission d'une information préoccupante – à l'usage des professionnels »](https://cotesdarmor.fr/sites/default/files/2023-05/202303_Formulaire_Transmission_IP_Professionnels.pdf),
avec la charte graphique de cotesdarmor.fr.

* **Formulaire public** (`/`) : les 8 rubriques du PDF (mineurs et autres enfants en lignes ajoutables, pièces jointes facultatives).
  La fiche est envoyée **par e-mail** à l'adresse paramétrée ; elle n'est jamais enregistrée.
* **Données conservées** : uniquement l'expéditeur (nom + e-mail du service qui transmet) et la date d'envoi (table `transmission_log`).
* **Backoffice sécurisé** (`/admin`) : connexion par identifiant/mot de passe (CSRF, limitation à 5 tentatives / 15 min),
  réglage de l'adresse destinataire, historique des envois, changement de mot de passe.
* Protections du formulaire public : jeton CSRF, champ piège anti-robot, limitation à 5 envois / 10 min / IP, en-têtes de sécurité
  (CSP, `X-Frame-Options`, `Cache-Control: no-store`…).

## Installation

```bash
composer install
cp .env .env.local          # puis renseigner APP_SECRET, MAILER_DSN, MAIL_FROM, DEFAULT_RECIPIENT
php bin/console doctrine:migrations:migrate -n
php bin/console app:create-admin prenom.nom@exemple.fr   # demande le mot de passe (12 car. min.)
php -S 127.0.0.1:8000 -t public                           # ou un vrai serveur web pointant sur public/
```

Variables d'environnement importantes :

| Variable | Rôle |
|---|---|
| `APP_SECRET` | secret Symfony (obligatoire, aléatoire) |
| `MAILER_DSN` | transport SMTP, ex. `smtp://user:pass@smtp.exemple.fr:587` (utiliser TLS) |
| `MAIL_FROM` | adresse expéditrice des messages |
| `DEFAULT_RECIPIENT` | destinataire tant que le backoffice n'en a pas défini un |
| `DATABASE_URL` | SQLite par défaut ; pour MySQL/PostgreSQL, régénérer la migration (`doctrine:migrations:diff`) |

En production : `APP_ENV=prod`, HTTPS obligatoire, `composer install --no-dev -o`, et ne pas installer d'outil qui journalise
le contenu des requêtes (profiler, logs de corps de requête…).

## Tests

```bash
php bin/phpunit
```
