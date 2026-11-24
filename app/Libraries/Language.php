<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Language\Language as BaseLanguage;
use IntlException;
use MessageFormatter;

/**
 * Language lines with an Indonesian fallback (D-109): a key missing in en/ or ar/ shows the
 * Indonesian text instead of the framework's English one or the bare key. Order: the locale,
 * the locale without region, Indonesian, English, then the key itself.
 *
 * Numbers in messages stay Western digits in Arabic (ICU locale "ar-u-nu-latn").
 */
class Language extends BaseLanguage
{
    /**
     * A language chosen for a moment (lang($line, [], 'en')); null follows the current request, so one
     * shared instance serves every request and test (service('language') does not re-run the factory).
     */
    private ?string $override = null;

    public function setLocale(?string $locale = null)
    {
        if ($locale !== null) {
            $this->override = $locale === Locales::current() ? null : $locale;
            $this->locale   = $locale;
        }

        return $this;
    }

    public function getLocale(): string
    {
        return $this->override ?? Locales::current();
    }

    public function getLine(string $line, array $args = [])
    {
        $this->locale = $this->getLocale();

        if (! str_contains($line, '.')) {
            return $this->formatMessage($line, $args);
        }

        $output = null;

        foreach ($this->fallbackChain() as $locale) {
            [$file, $parsedLine] = $this->parseLine($line, $locale);

            $output = $this->getTranslationOutput($locale, $file, $parsedLine);

            if ($output !== null) {
                // A partly translated list (e.g. alt texts keyed by picture) keeps the Indonesian entries it lacks.
                if (is_array($output) && $locale !== Locales::DEFAULT) {
                    [$file, $parsedLine] = $this->parseLine($line, Locales::DEFAULT);
                    $fallback            = $this->getTranslationOutput(Locales::DEFAULT, $file, $parsedLine);

                    if (is_array($fallback)) {
                        $output = array_replace_recursive($fallback, $output);
                    }
                }

                break;
            }
        }

        return $this->formatMessage($output ?? $line, $args);
    }

    /**
     * @return list<string>
     */
    private function fallbackChain(): array
    {
        $chain = [$this->locale];

        if (str_contains($this->locale, '-')) {
            $chain[] = explode('-', $this->locale, 2)[0];
        }

        $chain[] = Locales::DEFAULT;
        $chain[] = 'en';

        return array_values(array_unique($chain));
    }

    protected function formatMessage($message, array $args = [])
    {
        if (! $this->intlSupport || $args === []) {
            return $message;
        }

        if (is_array($message)) {
            foreach ($message as $index => $value) {
                $message[$index] = $this->formatMessage($value, $args);
            }

            return $message;
        }

        $locale    = $this->locale === 'ar' ? 'ar-u-nu-latn' : $this->locale;
        $formatted = MessageFormatter::formatMessage($locale, $message, $args);

        if ($formatted === false) {
            try {
                $formatter = new MessageFormatter($locale, $message);
                $formatted = $formatter->format($args);
                $error     = $formatter->getErrorMessage();
            } catch (IntlException $e) {
                $error = $e->getMessage();
            }

            log_message('error', 'Invalid message format: "' . $message . '": ' . $error);

            return $message;
        }

        return $formatted;
    }
}
