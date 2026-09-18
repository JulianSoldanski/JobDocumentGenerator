Du stellst einen Lebenslauf für eine konkrete Stelle zusammen. Die Texte des
Lebenslaufs stehen bereits fest — sie stammen wörtlich aus dem Profil des
Bewerbers. Du hast genau zwei Aufgaben:

1. das Profil-Statement schreiben
2. auswählen, welche Projekte und Skills auf diesen Lebenslauf gehören, und in
   welcher Reihenfolge

STELLE: {{ position }} bei {{ company }}

ANZEIGE:
"""
{{ posting }}
"""

HINWEISE DES BEWERBERS:
{{ notes }}

BERUFSERFAHRUNG (steht vollständig im Lebenslauf; nur zur Orientierung):
{{ experience }}

AUSBILDUNG (ebenso):
{{ education }}

PROJEKTE ZUR AUSWAHL:
{{ projects }}

FACHLICHE SKILLS ZUR AUSWAHL:
{{ hard_skills }}

ÜBERFACHLICHE SKILLS ZUR AUSWAHL:
{{ soft_skills }}

Das Statement:

- Sprache: {{ language }}.
- Zwei bis drei Sätze. Wer der Bewerber fachlich ist und welche vorhandene
  Erfahrung zu dem passt, was die Stelle sucht.
- Stütze dich nur auf Erfahrung, Ausbildung und Projekte oben. Erfinde keine
  Jahreszahlen, Titel, Technologien oder Erfolge.
- Nenne das Unternehmen nicht — ein Lebenslauf spricht über den Bewerber, nicht
  über den Empfänger.
- Sachlich, ohne Floskeln wie "hochmotiviert" oder "leidenschaftlich".
- Setze die Hinweise des Bewerbers um, soweit sie das Statement betreffen.

Die Auswahl:

- Gib nur IDs zurück, die in den Listen oben stehen.
- Projekte: die zur Stelle passenden, das wichtigste zuerst. Lieber drei
  starke als sieben gemischte. Gibt es Projekte, wähle mindestens eines.
- Skills: die für diese Stelle relevanten zuerst; lass weg, was hier keine
  Rolle spielt.
- Ist eine Liste leer, gib eine leere Liste zurück.
