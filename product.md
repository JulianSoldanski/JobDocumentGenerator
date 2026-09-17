# CVCreater — Projektbeschreibung

## Worum es geht

CVCreater macht aus einer Stellenanzeige und einem gepflegten Profil einen
**zugeschnittenen Lebenslauf und ein passendes Anschreiben** — und behält
danach den Überblick über die Bewerbung.

Das Tool ist kein Text-Generator. Es ist ein Arbeitsplatz für den kompletten
Bewerbungsablauf: Stelle erfassen, Dokumente erzeugen, verschicken, Status
verfolgen, auswerten.

---

## Grundprinzipien

Vier Regeln, die das Verhalten des ganzen Tools bestimmen. Alles andere ist
Handwerk.

### 1. Der Lebenslauf wird zusammengesetzt, nicht geschrieben

Jede Station, jede Ausbildung, jedes Projekt, jeder Skill steht im Profil —
vom Nutzer geschrieben. Beim Generieren werden diese Texte **wörtlich** auf
das Dokument übernommen.

Die KI hat genau zwei Aufgaben:

- das Profil-Statement schreiben (2–3 Sätze, auf die Stelle bezogen)
- **auswählen**, welche Projekte und Skills auf *diesen* Lebenslauf gehören,
  und in welcher Reihenfolge

Was die KI an IDs zurückgibt, wird gegen die erlaubte Liste geprüft.
Erfundenes wird verworfen. Kommt eine leere oder unbrauchbare Auswahl zurück,
greift die vollständige Liste als Rückfallebene — eine schlechte Auswahl kann
nie stillschweigend einen ganzen Abschnitt vom Lebenslauf entfernen.

Ergebnis: im Lebenslauf steht kein Satz, den der Nutzer nicht selbst
geschrieben hat.

### 2. Zwei Sprachen, keine Maschinenübersetzung

Jeder Eintrag existiert auf Deutsch **und** Englisch — beides vom Nutzer
verfasst. Sprachneutrale Fakten (Firmenname, Daten, Sichtbarkeit) stehen nur
einmal da.

Fehlt eine Sprache, greift **feldweise** die andere, damit ein halb
übersetzter Eintrag trotzdem vollständig gedruckt wird. Im Profil-Editor
wird das sichtbar markiert (`EN fehlt`). Es wird nicht heimlich übersetzt —
der Leser sieht im Zweifel den Text, den der Nutzer tatsächlich geschrieben
hat.

### 3. Das Anschreiben ist anders — und darf es sein

Ein Anschreiben ist Fließtext für genau eine Stelle. Es wird also erzeugt,
nicht zusammengesetzt. Aber nicht in beliebiger Stimme:

Der Nutzer hinterlegt ein Beispiel-Anschreiben. Die KI destilliert daraus
**editierbare Stilregeln** (Tonfall, Satzbau, Wortwahl, Aufbau, Eigenheiten).
Der Nutzer korrigiert diese Regeln. Beim Generieren geht die **Regelliste** in
den Prompt, nicht das Beispiel.

Damit lässt sich die Stimme der KI steuern, ohne jedes Mal einen neuen
Beispielbrief zu schreiben.

### 4. Status ist eine Historie, kein Feld

Der Status einer Bewerbung wird nicht überschrieben. Jeder Wechsel ist ein
Ereignis mit Zeitstempel, angehängt an eine Liste. Der aktuelle Status ist nur
der letzte Eintrag darin.

Dadurch ist jede Auswertung — „durchschnittliche Tage von Versendet bis
1. Gespräch", „Absagequote nach dem zweiten Gespräch" — beantwortbar, ohne
das Datenmodell anzufassen.

---

## Der Ablauf

```
Stelle gefunden
      │
      ▼
   QUEUE ──────────── per Bookmarklet von jeder Seite aus erfassen
      │
      ▼
 GENERATOR ───────── Anzeige laden · Felder automatisch füllen
      │               Sprache, Layout, Umfang wählen
      │               → Lebenslauf + Anschreiben + Stellen-Übersicht
      │               → bearbeiten, Vorschau, exportieren
      ▼
 BEWERBUNGEN ─────── automatisch angelegt · Status pflegen · Feedback
      │               Snapshot: was wurde tatsächlich verschickt
      ▼
  STATISTIK ──────── Funnel · Verweildauer je Stufe · Absagen
```

Das **Profil** ist die Datenbasis, aus der alles gespeist wird, und liegt
neben diesem Ablauf.

---

## Die fünf Bereiche

### Generator

Der Arbeitsplatz. Zweigeteilt: links die Stelle, rechts das Dokument.

**Eingabe der Stelle** — entweder Text einfügen oder eine URL laden. Beim
Laden wird die Seite geholt, von Navigation, Skripten und Footer befreit und
auf reinen Text reduziert.

**Automatisches Ausfüllen** — eine KI-Abfrage liest aus der Anzeige:
Unternehmen, Position, Ansprechpartner, Ort, Postanschrift. Nur leere Felder
werden gefüllt; was der Nutzer selbst eingetragen hat, bleibt stehen.

**Einstellungen vor dem Generieren**
- Sprache: Deutsch oder Englisch
- Layout: Modern, Sidebar oder Classic
- Umfang: beides, nur Lebenslauf, nur Anschreiben
- Freitext-Hinweise („betone den Data-Teil", „erwähne den Umzug nach Berlin")

**Ergebnis** — drei Dinge auf einmal:
1. Lebenslauf-Inhalt (zusammengesetzt, siehe Prinzip 1)
2. Anschreiben (Betreff, Anrede, Absätze)
3. **Stellen-Übersicht**: Was macht das Unternehmen? Wen sucht es? Welche
   Technologien? — bleibt links stehen, sichtbar **während** rechts editiert
   wird. Darunter ausklappbar der bereinigte Originaltext.

**Bearbeiten** — jeder Teil des Dokuments ist editierbar: Profil-Statement,
die Bullet Points je Station, Ausbildungsdetails, Projektauswahl per Schalter,
Skill-Zeilen, jeder Absatz des Anschreibens. Zu jedem Textfeld gibt es
**„Text verbessern"**: eine Anweisung eingeben („kürzer", „konkreter",
„weniger Floskeln"), die KI schreibt das Feld um.

**Vorschau** — das fertige Dokument, so wie es gedruckt wird.

**Export** — Lebenslauf und Anschreiben als Datei.

**Zeitmessung** — im Hintergrund läuft eine Uhr, sobald eine Stelle
identifiziert ist. Beim Generieren wird die verstrichene Zeit der Bewerbung
gutgeschrieben. So ist später sichtbar, wie viel Aufwand in welche Bewerbung
geflossen ist.

---

### Queue

Die Sammelstelle für Stellen, die noch nicht bearbeitet sind.

**Erfassen** — ein Bookmarklet in der Lesezeichenleiste. Ein Klick auf einer
beliebigen Stellenseite schickt Adresse und Seitentitel in die Queue und
schließt sich selbst wieder. Kein Browser-Add-on, keine Berechtigungen.

Alternativ: Adresse direkt in der Queue-Ansicht einfügen, mit optionaler
Notiz.

**Aufräumen beim Eintragen** — Tracking-Parameter (`utm_*`, `gclid`, …) werden
entfernt, bevor gespeichert wird. Dieselbe Stelle, die über LinkedIn-Anzeige,
Google-Suche und Newsletter hereinkommt, landet deshalb nur einmal in der
Liste.

**Liste** — offene Einträge zuerst, davon die ältesten oben: die Queue wird
von hinten abgearbeitet. Erledigtes, Übersprungenes und Fehlgeschlagenes
liegt darunter im Archiv. Ein Zähler in der Navigation zeigt, wie viel offen
ist.

**Weiterverarbeiten** — „→ Generieren" öffnet den Eintrag im Generator und
füllt die Anzeige vor. Ist das Dokument erzeugt, wird der Queue-Eintrag
automatisch als erledigt markiert und mit der entstandenen Bewerbung
verknüpft.

**Zustände:** offen · in Arbeit · erledigt · übersprungen · fehlgeschlagen

---

### Profil

Die Datenbasis. Alles hier wird einmal gepflegt und dann immer wieder
verwendet.

**Berufserfahrung** — Firma, Zeitraum, Ort, Titel, Bullet Points.
Ein Sichtbarkeitsschalter pro Station (ältere Nebenjobs ausblenden, ohne sie
zu löschen). Sortierung automatisch: laufende Stationen zuerst, dann nach
Enddatum absteigend.

**Ausbildung** — Institution, Zeitraum, Ort, Abschluss, Details.

**Hard Skills · Soft Skills · Sprachen** — jeweils eine geordnete Liste;
Sprachen zusätzlich mit Niveau.

Alle Einträge zweisprachig nach Prinzip 2: Firma und Daten einmal, Titel und
Bullet Points je Sprache, mit Markierung, wo eine Sprache fehlt.

**Projekte** — zwei Detailtiefen:
- *kurz* für den Lebenslauf: Titel, ein bis zwei Sätze, Tags, optional Note
  und Link
- *ausführlich* für die separate **Projektliste**: Auftraggeber, Zeitraum,
  Teamgröße, Technologien, Rolle, Ausgangslage, eigener Beitrag, Ergebnis

Ein Schalter je Projekt entscheidet, ob es in der Projektliste erscheint.
Die Projektliste ist ein eigenes Dokument, das separat exportiert wird.

**Schreibstil** — Beispiel-Anschreiben einfügen, „Stil analysieren" drücken,
die entstehenden Regeln überarbeiten. Siehe Prinzip 3.

**Kontaktdaten** — Name, Anschrift, Telefon, E-Mail. Stehen im Kopf von
Lebenslauf und Anschreiben und werden getrennt vom übrigen Profil gehalten,
weil sie nirgendwo sonst hingehören.

**Import aus einer bestehenden PDF** — einen vorhandenen Lebenslauf
hochladen; der Text wird extrahiert und per KI in die Profilstruktur
überführt, als Startpunkt statt eines leeren Formulars.

---

### Bewerbungen

Der Tracker. Jedes Generieren legt hier automatisch einen Eintrag an —
dedupliziert über Unternehmen und Position, damit mehrfaches Generieren für
dieselbe Stelle keine Karteileichen produziert.

**Stufen**

```
Erstellt → Versendet → 1. Gespräch → 2. Gespräch → 3. Gespräch
                                                        ╲
                                                      Abgesagt
```

Eine Absage ist **keine sechste Stufe**, sondern ein Abbruch: Der Verlauf
bleibt sichtbar (man sieht, wie weit es ging), die Absage erscheint als
Markierung mit Datum. Reaktivieren stellt die zuletzt erreichte Stufe wieder
her — der zurückgelegte Weg geht nicht verloren.

**Pro Bewerbung sichtbar**
- Unternehmen, Position, Link zur Originalanzeige
- Bewerbungsdatum (automatisch beim Wechsel auf „Versendet", editierbar)
- wie lange die Bewerbung schon in der aktuellen Stufe liegt
- investierte Recherchezeit
- Freitext-Feedback
- **Snapshot**: der Lebenslaufs-Inhalt und der Anschreiben-Text, die für
  genau diese Bewerbung erzeugt wurden — Monate später noch nachvollziehbar

**Manuell anlegen** — für Bewerbungen, die außerhalb des Tools entstanden
sind, inklusive Startstufe und Datum.

---

### Statistik

Wertet die Stufen-Historie aus.

**Funnel** — wie viele Bewerbungen welche Stufe erreicht haben. „Erstellt"
und „Versendet" werden in der Darstellung als eine Zeile geführt: fachlich
sind sie dieselbe Stufe, und getrennt zu zählen verzerrt die Quote. Im
Datenmodell und im Verlauf bleiben sie getrennt.

**Verweildauer je Stufe** — wie lange eine Bewerbung typischerweise in einer
Stufe liegt. Median statt Durchschnitt, weil einzelne Ausreißer die
Aussage sonst unbrauchbar machen.

**Absagen** — aufgeschlüsselt danach, aus welcher Stufe heraus abgesagt
wurde. Filterbar: „zeig mir nur die Absagen nach dem ersten Gespräch".

**Verlauf** — Bewerbungen je Monat, damit Phasen erkennbar werden.

---

## Datenmodell

Konzeptionell, unabhängig von der Umsetzung.

```
Nutzer
 ├── Kontaktdaten                   Name, Anschrift, Telefon, E-Mail
 ├── Profileinträge                 Erfahrung · Ausbildung · Hard/Soft Skills · Sprachen
 │      sprachneutral: Firma/Institution, Zeitraum, Sichtbarkeit, Reihenfolge
 │      je Sprache:    Titel, Ort, Bullet Points
 ├── Projekte                       kurz (Lebenslauf) + ausführlich (Projektliste)
 │      sprachneutral: Auftraggeber, Zeitraum, Technologien, Tags, Link
 │      je Sprache:    Titel, Zusammenfassung, Rolle, Ausgangslage, Beitrag, Ergebnis
 ├── Schreibstil                    Beispieltext + destillierte Regeln
 ├── Queue-Einträge                 Adresse, Titel, Notiz, Zustand
 └── Bewerbungen                    Unternehmen, Position, Anzeige, Feedback, Recherchezeit
        ├── Stufen-Ereignisse       Stufe + Zeitpunkt, nur angehängt, nie geändert
        └── Dokumente               Typ (Lebenslauf/Anschreiben/Projektliste),
                                    Sprache, Layout, Inhalt, Fassung
```

Zwei Punkte, die leicht übersehen werden:

- **Stufen-Ereignisse werden nur angehängt.** Nie aktualisieren, nie löschen.
  Das ist die Grundlage der gesamten Statistik.
- **Dokumente sind eigene Datensätze, nicht Felder der Bewerbung.** Dadurch
  lassen sich mehrere Fassungen je Bewerbung halten — etwa zwei Layouts
  vergleichen — und ein erneutes Erzeugen des Anschreibens überschreibt nicht
  den Lebenslauf.

---

## KI-Funktionen im Überblick

| Funktion | Eingabe | Ausgabe |
|---|---|---|
| Felder auslesen | Stellenanzeige | Unternehmen, Position, Ansprechpartner, Ort, Anschrift |
| Stellen-Übersicht | Stellenanzeige | Was das Unternehmen macht · wen es sucht · Technologien |
| Lebenslauf-Auswahl | Anzeige + Profil | Profil-Statement + Auswahl und Reihenfolge von Projekten und Skills |
| Anschreiben | Anzeige + Profil + Stilregeln | Betreff, Anrede, Absätze |
| Stilanalyse | Beispiel-Anschreiben | Editierbare Stilregeln |
| Text verbessern | Textfeld + Anweisung | Umgeschriebener Text |
| PDF-Import | Lebenslauf als PDF | Profilstruktur als Startpunkt |
| Projektentwurf | Kurzbeschreibung | Entwurf der ausführlichen Projektbeschreibung |

Alle Prompts liegen als einzelne Textdateien vor, getrennt vom Code, damit
sie geändert werden können, ohne Anwendungslogik anzufassen.

**Zwei technische Eigenheiten, die nicht verloren gehen dürfen:**

- Der „Denkmodus" des Modells wird **abgeschaltet**. Bleibt er an, verbraucht
  das Modell sein Token-Budget unsichtbar, bevor der eigentliche Text
  beginnt — lange Prompts kommen dann leer zurück.
- Antworten, die IDs enthalten, werden gegen die erlaubte Liste gefiltert
  (siehe Prinzip 1).

---

## Technische Eckpunkte

- **Backend:** Laravel · **Frontend:** React
- **Anmeldung erforderlich**, Daten gehören je Nutzer. Alles — Profil,
  Projekte, Bewerbungen, Queue — hängt am Konto.
- **Jede Ansicht hat eine eigene Adresse.** Ein Neuladen bleibt dort, wo der
  Nutzer war; ein Link auf eine Bewerbung öffnet die Bewerbung.
- **KI-Aufrufe laufen im Hintergrund.** Der Browser wartet nicht auf drei
  hintereinander geschaltete Modellantworten; Teilergebnisse erscheinen,
  sobald sie da sind — die Stellen-Übersicht ist typischerweise vor dem
  Anschreiben fertig.
- **Dokumente werden serverseitig gerendert.** Sie müssen als Datei
  existieren, nicht nur im Browser: für den PDF-Export, für den Snapshot und
  für den Versand.
- **Echtes PDF**, kein Umweg über den Druckdialog. Die Layouts nutzen
  modernes CSS, das Rendering braucht deshalb eine Browser-Engine.
- **Demo-Zugang** als eigenes Konto mit fiktiven Daten — für Screencasts und
  zum Ausprobieren, ohne echte Daten anzufassen.

---

## Was das Tool bewusst nicht tut

- **Keine Stapelverarbeitung.** „Alle offenen Stellen auf einmal generieren"
  klingt verlockend, aber jede Bewerbung muss ohnehin gelesen und korrigiert
  werden. Vollautomatik senkt nur die Qualität.
- **Kein Scraping von Jobportalen.** Die sind gut geschützt, und der Aufwand
  steht in keinem Verhältnis. Das Bookmarklet ist die bewusst einfache
  Antwort.
- **Keine Übersetzung von Profilinhalten zur Laufzeit.** Siehe Prinzip 2.
