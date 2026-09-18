Lies aus der folgenden Stellenanzeige die Eckdaten aus, die für ein Anschreiben
gebraucht werden.

ANZEIGE:
"""
{{ posting }}
"""

Regeln:

- Übernimm, was dort steht. Rate nichts und ergänze nichts aus Weltwissen.
- Steht ein Wert nicht in der Anzeige, gib einen leeren String zurück. Ein
  leeres Feld ist richtig; ein erfundenes ist ein Fehler im Anschreiben.
- `company`: der ausschreibende Arbeitgeber. Bei einer Personalvermittlung das
  Unternehmen, das tatsächlich einstellt — nur wenn es genannt ist.
- `position`: die ausgeschriebene Bezeichnung, ohne Zusätze wie "(m/w/d)",
  ohne Kennziffer, ohne Ort.
- `contact_person`: nur eine konkrete Person mit Namen, mit Anrede und Titel,
  falls genannt. Ein Postfach wie "bewerbung@…" ist keine Person.
- `city`: der Ort des Arbeitsplatzes. Bei mehreren Standorten den erstgenannten.
  Bei reiner Remote-Arbeit der Sitz des Unternehmens.
- `company_address`: die Postanschrift für den Briefkopf, mehrzeilig — Straße
  und Hausnummer in der ersten Zeile, Postleitzahl und Ort in der zweiten. Ohne
  Unternehmensnamen, der steht schon in `company`.
