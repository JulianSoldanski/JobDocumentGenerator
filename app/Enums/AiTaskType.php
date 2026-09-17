<?php

namespace App\Enums;

enum AiTaskType: string
{
    case ExtractFields = 'extract_fields';
    case JobSummary = 'job_summary';
    case CvSelection = 'cv_selection';
    case CoverLetter = 'cover_letter';
    case StyleAnalysis = 'style_analysis';
    case ImproveText = 'improve_text';
    case CvImport = 'cv_import';
    case ProjectDraft = 'project_draft';

    public function label(): string
    {
        return match ($this) {
            self::ExtractFields => 'Felder auslesen',
            self::JobSummary => 'Stellen-Übersicht',
            self::CvSelection => 'Lebenslauf-Auswahl',
            self::CoverLetter => 'Anschreiben',
            self::StyleAnalysis => 'Stilanalyse',
            self::ImproveText => 'Text verbessern',
            self::CvImport => 'PDF-Import',
            self::ProjectDraft => 'Projektentwurf',
        };
    }
}
