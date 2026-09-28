<?php

declare(strict_types=1);

namespace App;

final class BookValidator
{
    public const MAX_TEXT = 255;
    public const MAX_ANNOTATION = 5000;
    public const MIN_YEAR = 1000;

    /**
     * Ověří data z formuláře.
     *
     * @param array<string, mixed> $input
     * @return array{0: array{title: string, author: string, year: int, annotation: ?string, rating: ?int}, 1: array<string, string>}
     */
    public static function validate(array $input): array
    {
        $errors = [];

        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            $errors['title'] = 'Vyplňte název.';
        } elseif (mb_strlen($title) > self::MAX_TEXT) {
            $errors['title'] = sprintf('Název může mít nejvýše %d znaků.', self::MAX_TEXT);
        }

        $author = trim((string) ($input['author'] ?? ''));
        if ($author === '') {
            $errors['author'] = 'Vyplňte autora.';
        } elseif (mb_strlen($author) > self::MAX_TEXT) {
            $errors['author'] = sprintf('Autor může mít nejvýše %d znaků.', self::MAX_TEXT);
        }

        $maxYear = (int) date('Y') + 1;
        $year = filter_var($input['year'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => self::MIN_YEAR, 'max_range' => $maxYear],
        ]);
        if ($year === false) {
            $errors['year'] = sprintf('Zadejte rok mezi %d a %d.', self::MIN_YEAR, $maxYear);
        }

        $rating = null;
        $rawRating = (string) ($input['rating'] ?? '');
        if ($rawRating !== '') {
            $rating = filter_var($rawRating, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
            if ($rating === false) {
                $errors['rating'] = 'Hodnocení musí být 1 až 5.';
                $rating = null;
            }
        }

        $annotation = trim((string) ($input['annotation'] ?? ''));
        if (mb_strlen($annotation) > self::MAX_ANNOTATION) {
            $errors['annotation'] = sprintf('Anotace může mít nejvýše %d znaků.', self::MAX_ANNOTATION);
        }

        $data = [
            'title' => $title,
            'author' => $author,
            'year' => $year === false ? 0 : $year,
            'annotation' => $annotation === '' ? null : $annotation,
            'rating' => $rating,
        ];

        return [$data, $errors];
    }
}
