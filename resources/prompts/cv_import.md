Unten steht der Text eines Lebenslaufs, der aus einer PDF-Datei extrahiert
wurde. Überführe ihn in die vorgegebene Struktur.

LEBENSLAUF:
"""
{{ text }}
"""

Regeln:

- Das ist Extraktion, keine Übersetzung und keine Überarbeitung. Übernimm jeden
  Text in der Sprache und der Formulierung, in der er dasteht.
- "language" ist die Sprache, in der der Lebenslauf geschrieben ist: "de" oder
  "en".
- Datumsangaben als JJJJ-MM. Lässt sich ein Monat nicht bestimmen, bleibt das
  Feld leer. Eine laufende Station bekommt kein Enddatum und "is_current":
  "true".
- Erfinde nichts. Fehlt ein Abschnitt im Lebenslauf, bleibt die Liste leer.
- Ein Aufzählungspunkt im Lebenslauf wird genau ein Punkt in "bullets".
- Trenne Fähigkeiten sauber: Technologien und Methoden nach "hard_skills",
  überfachliches nach "soft_skills", Sprachkenntnisse mit Niveau nach
  "languages".
