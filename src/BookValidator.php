<?php

declare(strict_types=1);

namespace App;

final class BookValidator
{
    public const MAX_TEXT = 255;
    public const MAX_ANNOTATION = 5000;
    public const MIN_YEAR = 1000;

    public static function maxYear(): int
    {
        return (int) date('Y') + 1;
    }

    /**
     * Ověří data z formuláře nebo importu.
     *
     * @param array<mixed> $input
     * @return array{0: array{title: string, author: string, year: int, annotation: ?string, rating: ?int}, 1: array<string, string>}
     */
    public static function validate(array $input): array
    {
        // Pole a objekty (např. title[]=x) se berou jako prázdná hodnota
        $text = static fn (string $key): string => is_scalar($input[$key] ?? null) ? trim((string) $input[$key]) : '';
        $errors = [];

        $title = $text('title');
        if ($title === '') {
            $errors['title'] = 'Vyplňte název.';
        } elseif (mb_strlen($title) > self::MAX_TEXT) {
            $errors['title'] = sprintf('Název může mít nejvýše %d znaků.', self::MAX_TEXT);
        }

        $author = $text('author');
        if ($author === '') {
            $errors['author'] = 'Vyplňte autora.';
        } elseif (mb_strlen($author) > self::MAX_TEXT) {
            $errors['author'] = sprintf('Autor může mít nejvýše %d znaků.', self::MAX_TEXT);
        }

        $year = filter_var($text('year'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => self::MIN_YEAR, 'max_range' => self::maxYear()],
        ]);
        if ($year === false) {
            $errors['year'] = sprintf('Zadejte rok mezi %d a %d.', self::MIN_YEAR, self::maxYear());
        }

        $rating = $text('rating') === ''
            ? null
            : filter_var($text('rating'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
        if ($rating === false) {
            $errors['rating'] = 'Hodnocení musí být 1 až 5.';
        }

        $annotation = $text('annotation');
        if (mb_strlen($annotation) > self::MAX_ANNOTATION) {
            $errors['annotation'] = sprintf('Anotace může mít nejvýše %d znaků.', self::MAX_ANNOTATION);
        }

        $data = [
            'title' => $title,
            'author' => $author,
            'year' => (int) $year,
            'annotation' => $annotation === '' ? null : $annotation,
            'rating' => $rating ?: null,
        ];

        return [$data, $errors];
    }
}
