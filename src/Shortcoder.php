<?php

namespace Seiler\Shortcoder;

class Shortcoder
{
    /**
     * The shortcodes stack
     *
     * @var array
     */
    protected $shortcodes = [];

    /**
     * Create a new Shortcoder instance
     *
     * @param array|null|string $pattern
     * @param null|string       $replacement
     * @param mixed             $regex
     */
    public function __construct($pattern = null, $replacement = null, $regex = null)
    {
        $this->set($pattern, $replacement, $regex);
    }

    /**
     * Add shortcodes to the stack
     *
     * @param  array|null|string $pattern
     * @param  null|string       $replacement
     * @param  mixed             $regex
     *
     * @return \Seiler\Shortcoder\Shortcoder
     */
    public function add($pattern = null, $replacement = null, $regex = null)
    {
        // Called with arguments
        if (!is_array($pattern)) {
            $pattern = compact('pattern', 'replacement', 'regex');
        }

        // Called with only one shortcode
        if (!is_array(reset($pattern))) {
            $pattern = [$pattern];
        }

        foreach ($pattern as $attributes) {
            $pattern = array_key_exists('pattern', $attributes) ?
                $attributes['pattern'] : key($attributes);

            if (empty($pattern)) {
                continue;
            }

            $replacement = array_key_exists('replacement', $attributes) ?
                $attributes['replacement'] : reset($attributes);

            $regex = array_key_exists('regex', $attributes) ?
                $attributes['regex'] : null;

            $shortcode = [
                'pattern'     => $this->formatPattern($pattern, $regex),
                'replacement' => $this->formatReplacement($replacement, $regex)
            ];

            if (!in_array($shortcode, $this->shortcodes, true)) {
                $this->shortcodes[] = $shortcode;
            }
        }

        return $this;
    }

    /**
     * Format the given pattern
     *
     * @param  string $pattern
     * @param  mixed  $regex
     *
     * @return string
     */
    protected function formatPattern($pattern, $regex = null)
    {
        if (!empty($regex)) {
            return $pattern;
        }

        $pattern = preg_quote($pattern, '/');

        $pattern = preg_replace([
            '/\\\\\*/',        // 1 Replace all '*'...
            '/\s+/',           // 2 Replace all white-spaces...
            '/(\(\.\*\?\))$/', // 3 Replace any closing lazy catch-all...
        ], [
            '(.*?)',           // 1 ...with a lazy catch-all
            '\s',              // 2 ...with a 'white-space' regex pattern
            '(.*)'             // 3 ...with a greedy one
        ], $pattern);

        return "/$pattern/s";
    }

    /**
     * Format the given replacement
     *
     * @param  string $replacement
     * @param  mixed  $regex
     *
     * @return string
     */
    protected function formatReplacement($replacement = '', $regex = null)
    {
        if (!empty($regex)) {
            return $replacement;
        }

        $backReferences = [];

        if (preg_match_all('/\$(\d){1,2}/', $replacement, $matches)) {
            $backReferences = $matches[1];
        }

        while (strpos($replacement, '*') !== false) {
            $index = 1;

            while (in_array((string)$index, $backReferences, true)) {
                $index++;
            }

            $backReferences[] = (string)$index;
            $replacement = preg_replace('/\*/', '\$' . $index, $replacement, 1);
        }

        return $replacement;
    }

    /**
     * Parse the given text with stacked shortcodes
     *
     * @param  string $text
     *
     * @return string
     */
    public function parse($text = '')
    {
        return preg_replace(
            array_column($this->shortcodes, 'pattern'),
            array_column($this->shortcodes, 'replacement'),
            $text
        );
    }

    /**
     * Replace the stack with new shortcodes
     *
     * @param  array|null|string $pattern
     * @param  null|string       $replacement
     * @param  mixed             $regex
     *
     * @return \Seiler\Shortcoder\Shortcoder
     */
    public function set($pattern = null, $replacement = null, $regex = null)
    {
        return $this->flush()->add($pattern, $replacement, $regex);
    }

    /**
     * Flush the stack
     *
     * @return \Seiler\Shortcoder\Shortcoder
     */
    public function flush()
    {
        $this->shortcodes = [];

        return $this;
    }
}
