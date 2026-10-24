# Kunena Solved Plugin

A Kunena 6 plugin that lets topic authors, moderators and administrators mark a forum topic as **solved**, with one click.

The old "Kunena Solved" plugin by yagendoo only supported Joomla 3 / Kunena 5 and has been discontinued. This plugin is a new implementation for **Joomla 5** and **Kunena 6.x**.

**[Deutsche Version weiter unten](#deutsch)**

---

## Features

- **"Mark as solved" button** in the topic action bar (solid green, white text). Once marked, it switches to a grey **"Mark as unsolved"** button, so the mark can be undone.
- **Title prefix:** "My problem" becomes "[Solved] My problem". The prefix is configurable. It is also written to the first post, so editing the first post later keeps it. Detection is case-insensitive, so titles prefixed by hand (e.g. `[GELÖST]`) are recognised too.
- **Topic icon:** sets Kunena's built-in "solved" topic icon (green check, ID 8 in the default set). Unmarking resets it to the default icon.
- **Notice post (optional):** adds a short reply such as "This topic has been marked as solved." in the name of the user who marked it. The text is configurable and supports BBCode.
- **Lock topic (optional):** locks the topic when it is marked as solved, and unlocks it again when unmarked.
- **Permissions:** topic author, category moderators and Kunena administrators can each be enabled or disabled separately.
- **Languages:** English (en-GB) and German (de-DE).

## Requirements

- Joomla 5.x
- Kunena 6.0 or newer (tested against the 6.3 / 6.4 code base)
- PHP 8.1+

## Installation

1. Download the latest ZIP from [`dist/`](dist/) (currently `plg_kunena_solved_v1.0.2.zip`).
2. In Joomla go to **System → Install → Extensions** and upload the ZIP.
3. Go to **System → Manage → Plugins**, search for **"Kunena - Solved"** and enable it.
4. Check the plugin settings (see below).
5. For the icon to show, topic icons must be enabled in **Kunena → Configuration**.

To update, simply install the new ZIP over the old version; settings are kept.

## Settings

| Setting | Default | Description |
|---|---|---|
| Topic author may mark | Yes | The author sees the button in their own topics. |
| Moderators may mark | Yes | Category moderators see the button in all topics. |
| Administrators may mark | Yes | Kunena administrators see the button in all topics. |
| Title prefix | *language default* | `[Solved]` (en) / `[Gelöst]` (de). **On multilingual sites enter a fixed prefix**, otherwise solved topics are only recognised in one language. |
| Set topic icon | Yes | Sets the "solved" topic icon. |
| Icon ID | 8 | Topic icon ID in your Kunena icon set. |
| Add notice post | Yes | Adds a short reply when a topic is marked as solved. |
| Notice post text | *language default* | BBCode allowed. |
| Lock topic | No | Locks the topic when marked as solved. |

## How it works

- The button is collected via Kunena's `onKunenaGetButtons` event. Kunena templates only render buttons they know by name, so the plugin injects the button HTML into the topic action bar in `onAfterRender`. It looks for `id="topic-actions"`, `id="topic-actions-toolbar"` or `class="topic-actions-toolbar"`. If none is found (other templates), the button is shown at the top of the forum output.
- Clicking the button calls `index.php?option=com_ajax&group=kunena&plugin=solved&task=solve|unsolve&id=…`. The request is protected with a Joomla form token, and permissions are checked again on the server.
- "Solved" is detected by the title prefix, so no extra database table is needed. If you change the prefix later, topics with the old prefix count as unsolved.

## Notes

- In moderated categories the notice post of a regular member must be approved like any other post.
- If unmarking is used together with "Lock topic", the topic is unlocked again, even if a moderator had locked it for another reason.

## License

GNU General Public License v3.0 or later. See [LICENSE](LICENSE).

---

<a id="deutsch"></a>

## Deutsch

Kunena-6-Plugin, mit dem Thema-Ersteller, Moderatoren und Administratoren ein Forenthema mit einem Klick als **gelöst** markieren können. Es ersetzt das eingestellte yagendoo-Plugin „Kunena Solved“, das nur Joomla 3 / Kunena 5 unterstützte, und läuft mit **Joomla 5** und **Kunena 6.x**.

### Funktionen

- **Button „Als gelöst markieren“** in der Aktionsleiste des Themas (flächig grün, weiße Schrift). Danach erscheint ein grauer Button **„Als ungelöst markieren“**, mit dem sich die Markierung zurücknehmen lässt.
- **Titel-Präfix:** Aus „Mein Problem“ wird „[Gelöst] Mein Problem“. Das Präfix ist frei einstellbar und wird auch in den ersten Beitrag geschrieben. Groß- und Kleinschreibung spielen bei der Erkennung keine Rolle, von Hand gesetzte Präfixe wie `[GELÖST]` werden also ebenfalls erkannt.
- **Themen-Icon:** setzt das Kunena-Icon „Gelöst“ (grüner Haken, ID 8). Beim Zurücknehmen wird das Standard-Icon gesetzt.
- **Hinweis-Beitrag (optional):** hängt einen kurzen Beitrag an, z. B. „Dieses Thema wurde als gelöst markiert.“. Der Text ist einstellbar, BBCode ist erlaubt.
- **Thema sperren (optional):** sperrt das Thema beim Markieren und entsperrt es beim Zurücknehmen wieder.
- **Rechte:** Ersteller, Moderatoren und Administratoren lassen sich einzeln an- und abschalten.
- **Sprachen:** Deutsch und Englisch.

### Installation

1. Aktuelles ZIP aus [`dist/`](dist/) herunterladen.
2. In Joomla unter **System → Installieren → Erweiterungen** hochladen.
3. Unter **System → Verwalten → Plugins** das Plugin **„Kunena - Gelöst“** aktivieren.
4. Einstellungen prüfen. Auf **mehrsprachigen Seiten ein festes Präfix eintragen** (z. B. `[GELÖST]`), sonst werden gelöste Themen nur in einer Sprache erkannt.
5. Damit das Icon erscheint, müssen Themen-Icons in der **Kunena-Konfiguration** aktiviert sein.

Für ein Update das neue ZIP einfach über die alte Version installieren, die Einstellungen bleiben erhalten.

### Hinweise

- In moderierten Kategorien muss der Hinweis-Beitrag eines normalen Mitglieds wie jeder andere Beitrag freigegeben werden.
- Ist „Thema sperren“ aktiv, wird das Thema beim Zurücknehmen wieder entsperrt, auch wenn ein Moderator es aus einem anderen Grund gesperrt hatte.

### Lizenz

GNU General Public License v3.0 oder neuer, siehe [LICENSE](LICENSE).
