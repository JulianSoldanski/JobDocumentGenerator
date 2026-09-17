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
