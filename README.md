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

## Pièce jointe PDF

Chaque e-mail contient en pièce jointe la fiche au format PDF (`fiche-transmission-ip-AAAAMMJJ-HHMMSS.pdf`), qui reprend la
structure du formulaire papier de la CRIP (rubriques 1 à 8, cases à cocher, tableaux, encarts « N. B. »). Le PDF est généré en
mémoire par `App\Service\TransmissionPdf` (dompdf, gabarit `templates/pdf/fiche.html.twig`) et n'est jamais écrit sur le disque ;
le nom du fichier ne contient aucune donnée nominative. Les pièces jointes téléversées sont ajoutées après la fiche.

## Docker (derrière Traefik)

L'image (`Dockerfile`) repose sur FrankenPHP (Caddy + PHP 8.3) : elle sert l'application en **HTTP sur le port 8080**, sous un
utilisateur non-root, sans capabilities. Le **TLS est terminé par Traefik**, qui doit être déjà en place avec un réseau Docker
partagé (par défaut `traefik`), les entrypoints `web` / `websecure` et un certresolver (par défaut `letsencrypt`).

```bash
cp .env.docker.example .env.docker      # renseigner APP_HOST, APP_SECRET, MAILER_DSN, MAIL_FROM, DEFAULT_RECIPIENT
docker compose --env-file .env.docker up -d --build
docker compose --env-file .env.docker exec app php bin/console app:create-admin prenom.nom@exemple.fr
```

* Les routeurs Traefik (HTTPS + redirection HTTP → HTTPS) sont déclarés par les labels de `compose.yaml`.
* Les migrations sont appliquées automatiquement au démarrage du conteneur.
* La base SQLite est dans le volume `crip-data` (`/var/lib/crip`) : c'est la seule donnée persistante (expéditeur + date d'envoi).
* `TRUSTED_PROXIES` (défaut `private_ranges`) permet à Symfony de lire l'IP cliente et le schéma HTTPS transmis par Traefik
  (limitation de débit par IP, cookies `Secure`, URLs générées en HTTPS).
* Les envois passent par `MAILER_DSN` : le conteneur doit pouvoir joindre votre serveur SMTP.

## Tests

```bash
php bin/phpunit
```
