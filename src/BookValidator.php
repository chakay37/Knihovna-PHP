<?php

declare(strict_types=1);

namespace App;

// final: validátor je statická utilita.
final class BookValidator
{
    public const MAX_TEXT = 255; // Max. délka pro název i autora.
    public const MAX_ANNOTATION = 1000; // Max. délka anotace.
    public const MIN_YEAR = 1; // Nejstarší rok vydání, který formulář ještě přijme.
    public const FUTURE_BOOK_YEAR = 5; // Určuje kolik let před vydáním knihy, lze knihu zadat do formuláře.

    // Nejvyšší povolený rok vydání.
    // Knihu lze zadat do formuláře před FUTURE_BOOK_YEAR let před vydáním knihy.
    public static function maxYear(): int
    {
        return (int) date('Y') + self::FUTURE_BOOK_YEAR;
    }

    /**
     * Ověří data z formuláře nebo importu.
     *
     * @param array<mixed> $input data z $_POST nebo z dekódovaného JSON při importu
     * @return array{0: array{title: string, author: string, year: int, annotation: ?string, rating: ?int}, 1: array<string, string>}
     *         Dvojice [vyčištěná data, chyby podle názvu pole]. Chyby jsou [], když jsou data v pořádku.
     */
    public static function validate(array $input): array
    {

        $text = static fn (string $key): string => is_scalar($input[$key] ?? null) ? trim((string) $input[$key]) : '';
        $errors = []; 

        // Název: povinný, s horním limitem délky.
        $title = $text('title');
        if ($title === '') {
            $errors['title'] = 'Vyplňte název.';
        } elseif (mb_strlen($title) > self::MAX_TEXT) {
            // mb_strlen (ne strlen): počítá znaky, ne byty – důležité pro diakritiku/víceznakové UTF-8 znaky.
            $errors['title'] = sprintf('Název může mít nejvýše %d znaků.', self::MAX_TEXT);
        }

        // Autor: povinný, s horním limitem délky.
        $author = $text('author');
        if ($author === '') {
            $errors['author'] = 'Vyplňte autora.';
        } elseif (mb_strlen($author) > self::MAX_TEXT) {
            $errors['author'] = sprintf('Autor může mít nejvýše %d znaků.', self::MAX_TEXT);
        }

        // Rok vydání: musí být celé číslo v rozsahu MIN_YEAR..maxYear().
        $year = filter_var($text('year'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => self::MIN_YEAR, 'max_range' => self::maxYear()],
        ]);
        if ($year === false) {
            $errors['year'] = sprintf('Zadejte rok mezi %d a %d.', self::MIN_YEAR, self::maxYear());
        }

        // Hodnocení: musí být celé číslo 1 až 5.
        $rating = $text('rating') === ''
            ? null
            : filter_var($text('rating'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
        if ($rating === false) {
            $errors['rating'] = 'Hodnocení musí být 1 až 5.';
        }

        // Anotace: horní limit délky.
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
