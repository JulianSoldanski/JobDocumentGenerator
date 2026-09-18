export type Language = 'de' | 'en';

/** Ein vom Nutzer geschriebener Textblock einer Sprache. */
export type TranslationBlock = Record<string, string | string[]>;

export type Translations = Record<Language, TranslationBlock>;

export type ProfileSection =
    | 'experience'
    | 'education'
    | 'hard_skill'
    | 'soft_skill'
    | 'language';

export type ProfileEntry = {
    id: number;
    section: ProfileSection;
    organization: string;
    start_month: string | null;
    end_month: string | null;
    is_current: boolean;
    is_visible: boolean;
    position: number;
    translations: Translations;
    headline: string;
    missing_languages: Language[];
};

export type Project = {
    id: number;
    position: number;
    is_visible: boolean;
    in_project_list: boolean;
    link: string | null;
    grade: string | null;
    tags: string[];
    client: string;
    period: string;
    team_size: string;
    technologies: string[];
    translations: Translations;
    headline: string;
    has_long_form: boolean;
    missing_languages: Language[];
};

export type WritingStyle = {
    example: string;
    rules: string[];
};

export type ContactDetails = {
    full_name: string;
    street: string;
    postal_code: string;
    city: string;
    phone: string;
    email: string;
};

export type DocumentLayout = 'modern' | 'sidebar' | 'classic';

export type GenerationScope = 'both' | 'cv' | 'letter';

/** Die Anzeige auf einen Blick — bleibt links stehen, während rechts editiert wird. */
export type JobSummary = {
    company: string;
    role: string;
    technologies: string[];
};

/** Der Arbeitsplatz für genau eine Stelle. */
export type GeneratorSession = {
    id: number;
    job_url: string;
    job_posting: string;
    company: string;
    position: string;
    contact_person: string;
    city: string;
    company_address: string;
    language: Language;
    layout: DocumentLayout;
    scope: GenerationScope;
    notes: string;
    summary: JobSummary | null;
    title: string;
    timer_started_at: string | null;
    application_id: number | null;
    documents: SessionDocuments;
};

/**
 * Ein erzeugtes Dokument. Die Fassung wechselt mit jedem Generieren und jeder
 * gespeicherten Änderung; `edited` sagt, ob Hand angelegt wurde.
 */
export type DocumentVersion = { id: number; version: number; edited: boolean };

type CvEntry = {
    id: string;
    organization: string;
    start_month: string | null;
    end_month: string | null;
    is_current: boolean;
};

/** Der Inhalt eines Lebenslaufs, so wie der Server ihn speichert. */
export type CvContent = {
    statement: string;
    experience: (CvEntry & { title: string; bullets: string[] })[];
    education: (CvEntry & { degree: string; details: string[] })[];
    projects: {
        id: string;
        title: string;
        summary: string;
        included: boolean;
    }[];
    skills: {
        hard: { name: string }[];
        soft: { name: string }[];
        languages: { name: string; level: string }[];
    };
};

/** Der Inhalt eines Anschreibens. */
export type LetterContent = {
    subject: string;
    salutation: string;
    paragraphs: string[];
    closing: string;
};

export type SessionDocuments = Partial<
    Record<'cv' | 'letter', DocumentVersion>
>;

export type ApplicationStage =
    | 'created'
    | 'sent'
    | 'interview_1'
    | 'interview_2'
    | 'interview_3'
    | 'rejected';

/** Eine Stufe mit ihrem Namen — die Liste kommt vom Server. */
export type StageOption = { value: ApplicationStage; label: string };

/** Eine Bewerbung im Tracker. */
export type Application = {
    id: number;
    company: string;
    position: string;
    job_url: string | null;
    stage: ApplicationStage;
    stage_since: string | null;
    /** Bei einer Absage: wie weit es ging. */
    highest_stage: ApplicationStage | null;
    applied_on: string | null;
    research_seconds: number;
    feedback: string;
};

/** Ein Stufenwechsel — nur angehängt, nie geändert. */
export type StageEvent = {
    id: number;
    stage: ApplicationStage;
    occurred_at: string;
};
