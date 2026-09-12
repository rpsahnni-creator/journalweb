<?php

namespace App\Enums;

enum RoleSlug: string
{
    case Admin = 'admin';
    case JournalManager = 'journal_manager';
    case EditorInChief = 'editor_in_chief';
    case SectionEditor = 'section_editor';
    case Reviewer = 'reviewer';
    case Author = 'author';
    case Reader = 'reader';
    case Editor = 'editor';
    case Copyeditor = 'copyeditor';

    /**
     * @return list<self>
     */
    public static function editorial(): array
    {
        return [
            self::Admin,
            self::JournalManager,
            self::EditorInChief,
            self::SectionEditor,
            self::Editor,
            self::Copyeditor,
        ];
    }

    /**
     * @return list<string>
     */
    public static function editorialValues(): array
    {
        return array_map(fn (self $role) => $role->value, self::editorial());
    }
}
