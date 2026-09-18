<?php

namespace App\Http\Controllers;

use App\Documents\DocumentEditor;
use App\Documents\DocumentRenderer;
use App\Enums\DocumentType;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Ein erzeugtes Dokument: ansehen, bearbeiten, als Vorschau rendern.
 */
class DocumentController extends Controller
{
    /**
     * Der Inhalt für den Editor.
     */
    public function show(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        return response()->json([
            'id' => $document->id,
            'type' => $document->type->value,
            'version' => $document->version,
            'content' => $document->content,
        ]);
    }

    /**
     * Änderungen aus dem Editor. Jede gespeicherte Änderung ist eine neue
     * Fassung — so lädt auch die Vorschau neu.
     */
    public function update(Request $request, Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        $edits = $request->validate(match ($document->type) {
            DocumentType::Cv => [
                'statement' => ['sometimes', 'nullable', 'string', 'max:2000'],
                'statement_included' => ['sometimes', 'boolean'],
                'experience' => ['sometimes', 'array'],
                'experience.*.id' => ['required', 'string'],
                'experience.*.bullets' => ['array'],
                'experience.*.bullets.*' => ['nullable', 'string', 'max:1000'],
                'experience.*.subtle' => ['sometimes', 'boolean'],
                'education' => ['sometimes', 'array'],
                'education.*.id' => ['required', 'string'],
                'education.*.details' => ['array'],
                'education.*.details.*' => ['nullable', 'string', 'max:1000'],
                'projects' => ['sometimes', 'array'],
                'projects.*.id' => ['required', 'string'],
                'projects.*.included' => ['required', 'boolean'],
                'skills' => ['sometimes', 'array'],
                'skills.hard' => ['sometimes', 'array'],
                'skills.hard.*' => ['nullable', 'string', 'max:200'],
                'skills.soft' => ['sometimes', 'array'],
                'skills.soft.*' => ['nullable', 'string', 'max:200'],
                'skills.languages' => ['sometimes', 'array'],
                'skills.languages.*.name' => ['nullable', 'string', 'max:100'],
                'skills.languages.*.level' => ['nullable', 'string', 'max:100'],
            ],
            DocumentType::Letter => [
                'subject' => ['sometimes', 'nullable', 'string', 'max:300'],
                'salutation' => ['sometimes', 'nullable', 'string', 'max:200'],
                'paragraphs' => ['sometimes', 'array'],
                'paragraphs.*' => ['nullable', 'string', 'max:5000'],
                'closing' => ['sometimes', 'nullable', 'string', 'max:200'],
            ],
            DocumentType::ProjectList => [],
        });

        $document->content = DocumentEditor::apply($document->type, $document->content, $edits);
        $document->version++;
        $document->invalidateRendering();
        $document->save();

        return response()->json([
            'id' => $document->id,
            'version' => $document->version,
            'edited' => true,
        ]);
    }

    /**
     * Die Vorschau zeigt das Dokument so, wie es gedruckt wird — gerendert
     * vom selben Code, der später das PDF erzeugt.
     */
    public function preview(Document $document, DocumentRenderer $renderer): Response
    {
        $this->authorize('view', $document);

        return response($renderer->render($document))
            ->header('Content-Type', 'text/html; charset=utf-8')
            // Die Vorschau hängt an der Fassung, nicht an der Adresse.
            ->header('Cache-Control', 'no-store');
    }
}
