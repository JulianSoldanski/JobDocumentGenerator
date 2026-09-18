<?php

namespace App\Documents;

use App\Enums\DocumentLayout;
use App\Enums\DocumentType;
use App\Enums\Language;
use App\Models\Document;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/**
 * Macht aus dem gespeicherten Inhalt eines Dokuments fertiges HTML.
 *
 * Gerendert wird serverseitig, weil das Dokument als Datei existieren muss:
 * für den PDF-Export, für den Snapshot und für den Versand.
 */
class DocumentRenderer
{
    public function render(Document $document): string
    {
        return $this->renderContent(
            $document->type,
            $document->language,
            $document->layout,
            $document->content,
        );
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public function renderContent(
        DocumentType $type,
        Language $language,
        ?DocumentLayout $layout,
        array $content,
    ): string {
        $format = new DocumentFormat($language);
        $layout ??= DocumentLayout::Modern;

        $data = [
            'language' => $language,
            'fmt' => $format,
            'styles' => $this->styles($type, $layout),
            'title' => $this->title($type, $format, $content),
            ...$this->viewData($type, $content),
        ];

        return View::make($this->view($type, $layout), $data)->render();
    }

    /** @return view-string */
    private function view(DocumentType $type, DocumentLayout $layout): string
    {
        return match ($type) {
            DocumentType::Cv => "documents.cv.{$layout->value}",
            DocumentType::Letter => 'documents.letter',
            DocumentType::ProjectList => 'documents.project-list',
        };
    }

    /**
     * Das CSS wird eingebettet, damit die Datei für sich allein steht.
     */
    private function styles(DocumentType $type, DocumentLayout $layout): string
    {
        // Jedes Dokument hat sein eigenes Stylesheet über der gemeinsamen
        // Grundlage — ein neues Layout kann so kein anderes Dokument verbiegen.
        $sheets = match ($type) {
            DocumentType::Cv => ['base', $layout->value],
            DocumentType::Letter => ['base', 'letter'],
            DocumentType::ProjectList => ['base', 'project-list'],
        };

        return collect($sheets)
            ->map(fn (string $sheet): string => File::get(resource_path("views/documents/styles/{$sheet}.css")))
            ->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function title(DocumentType $type, DocumentFormat $format, array $content): string
    {
        $name = match ($type) {
            DocumentType::Letter => (string) data_get($content, 'sender.full_name', ''),
            default => (string) data_get($content, 'contact.full_name', ''),
        };

        $label = match ($type) {
            DocumentType::Cv => $format->label('cv.title'),
            DocumentType::Letter => $format->label('letter.title'),
            DocumentType::ProjectList => $format->label('project_list.title'),
        };

        return trim($name === '' ? $label : "{$name} – {$label}");
    }

    /**
     * Die Vorlagen sollen nicht mit fehlenden Schlüsseln umgehen müssen: hier
     * bekommt jeder Abschnitt seine Form, auch wenn der Inhalt lückenhaft ist.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function viewData(DocumentType $type, array $content): array
    {
        return match ($type) {
            DocumentType::Cv => [
                'contact' => $this->contact($content),
                // Die Schalter im Editor entscheiden, was gedruckt wird.
                'statement' => data_get($content, 'statement_included', true)
                    ? trim((string) data_get($content, 'statement', ''))
                    : '',
                'experience' => $this->list($content, 'experience'),
                'education' => $this->list($content, 'education'),
                'projects' => array_values(array_filter(
                    $this->list($content, 'projects'),
                    fn (array $project): bool => (bool) ($project['included'] ?? true),
                )),
                'skills' => [
                    'hard' => $this->list($content, 'skills.hard'),
                    'soft' => $this->list($content, 'skills.soft'),
                    'languages' => $this->list($content, 'skills.languages'),
                ],
            ],
            DocumentType::Letter => [
                'sender' => $this->contact($content, 'sender'),
                'recipient' => [
                    'company' => (string) data_get($content, 'recipient.company', ''),
                    'contact_person' => (string) data_get($content, 'recipient.contact_person', ''),
                    'address' => (string) data_get($content, 'recipient.address', ''),
                ],
                'place' => (string) data_get($content, 'place', ''),
                'date' => (string) data_get($content, 'date', ''),
                'subject' => (string) data_get($content, 'subject', ''),
                'salutation' => (string) data_get($content, 'salutation', ''),
                'paragraphs' => array_values(array_filter(
                    array_map(
                        fn ($paragraph): string => trim((string) $paragraph),
                        (array) data_get($content, 'paragraphs', [])
                    ),
                    fn (string $paragraph): bool => $paragraph !== '',
                )),
                'closing' => (string) data_get($content, 'closing', ''),
            ],
            DocumentType::ProjectList => [
                'contact' => $this->contact($content),
                'projects' => $this->list($content, 'projects'),
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function contact(array $content, string $key = 'contact'): array
    {
        $lines = (array) data_get($content, "{$key}.address_lines", []);

        return [
            'full_name' => (string) data_get($content, "{$key}.full_name", ''),
            'address_lines' => array_values(array_filter(array_map(strval(...), $lines))),
            'address_line' => implode(' · ', array_filter(array_map(strval(...), $lines))),
            'phone' => (string) data_get($content, "{$key}.phone", ''),
            'email' => (string) data_get($content, "{$key}.email", ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<int, array<string, mixed>>
     */
    private function list(array $content, string $key): array
    {
        $items = data_get($content, $key, []);

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, is_array(...)));
    }
}
