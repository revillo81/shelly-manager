<?php
require __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin();

$pageTitle = t('help_title');
require __DIR__ . '/../templates/header.php';
?>
<h1><?= e(t('help_title')) ?></h1>

<?php if (Lang::current() === 'de'): ?>
<section class="card help-card">
    <h2>Erste Schritte</h2>
    <p>Shelly Manager verwaltet Shelly-Geräte zentral über deren lokale HTTP-API. Es wird keine Cloud-Verbindung benötigt.</p>
    <h3>Geräte hinzufügen</h3>
    <ul>
        <li><strong>Manuell:</strong> IP-Adresse (und ggf. Zugangsdaten) unter „Gerät hinzufügen“ eingeben. Der Typ wird automatisch erkannt.</li>
        <li><strong>Netzwerk-Scan:</strong> IP-Bereich in CIDR-Notation angeben (Standard: 192.168.1.0/24). Gefundene Geräte können ausgewählt und übernommen werden.</li>
    </ul>
    <h3>Passwortgeschützte Geräte</h3>
    <p>Ist auf einem Shelly-Gerät der Passwortschutz aktiv, kann in der Detailansicht Benutzername und Passwort hinterlegt werden. Diese werden verschlüsselt in der Datenbank gespeichert und für alle API-Aufrufe verwendet.</p>
    <h3>Baumstruktur & Gruppen</h3>
    <p>Auf der Startseite können Gruppen (z.B. Räume) angelegt werden. Geräte lassen sich in der Detailansicht einer Gruppe zuordnen.</p>
    <h3>Steuerung</h3>
    <p>In der Geräte-Detailansicht können Schalter/Rollläden direkt gesteuert und der Live-Status abgerufen werden.</p>
    <h3>Datenbank wechseln</h3>
    <p>Unter „Einstellungen“ kann von SQLite auf MariaDB/MySQL umgeschaltet werden. Bestehende Daten werden dabei nicht automatisch migriert.</p>
    <h3>Unterstützte Generationen</h3>
    <p>Shelly Gen1 (Classic, REST-API) und Gen2/Gen3 (Plus/Pro, RPC-API) werden unterstützt. Siehe offizielle API-Dokumentation: <a href="https://shelly-api-docs.shelly.cloud/" target="_blank" rel="noopener">shelly-api-docs.shelly.cloud</a>.</p>
</section>
<?php else: ?>
<section class="card help-card">
    <h2>Getting started</h2>
    <p>Shelly Manager manages Shelly devices centrally via their local HTTP API. No cloud connection is required.</p>
    <h3>Adding devices</h3>
    <ul>
        <li><strong>Manually:</strong> enter the IP address (and credentials if needed) under "Add device". The type is detected automatically.</li>
        <li><strong>Network scan:</strong> specify an IP range in CIDR notation (default: 192.168.1.0/24). Found devices can be selected and imported.</li>
    </ul>
    <h3>Password-protected devices</h3>
    <p>If password protection is enabled on a Shelly device, username and password can be stored in the device detail view. They are stored encrypted in the database and used for all API calls.</p>
    <h3>Tree structure & groups</h3>
    <p>Groups (e.g. rooms) can be created on the dashboard. Devices can be assigned to a group in the detail view.</p>
    <h3>Control</h3>
    <p>Switches/covers can be controlled directly and live status can be retrieved from the device detail view.</p>
    <h3>Switching database</h3>
    <p>Under "Settings" you can switch from SQLite to MariaDB/MySQL. Existing data is not migrated automatically.</p>
    <h3>Supported generations</h3>
    <p>Shelly Gen1 (Classic, REST API) and Gen2/Gen3 (Plus/Pro, RPC API) are supported. See the official API documentation: <a href="https://shelly-api-docs.shelly.cloud/" target="_blank" rel="noopener">shelly-api-docs.shelly.cloud</a>.</p>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../templates/footer.php'; ?>
