<?php

/**
 * This code checks if the class 'ParsedownExtra' exists. If it does, it creates an alias for it called 'ParsedownExtendedParentAlias'.
 * If the class 'ParsedownExtra' does not exist, it creates an alias for the class 'Parsedown' called 'ParsedownExtendedParentAlias'.
 */
if (class_exists('ParsedownExtra')) {
    class_alias('ParsedownExtra', 'ParsedownExtendedParentAlias');
} else {
    class_alias('Parsedown', 'ParsedownExtendedParentAlias');
}

/**
 * ParsedownExtended class extends the ParsedownExtendedParentAlias class and provides additional features and settings for parsing Markdown content.
 */
class ParsedownExtended extends ParsedownExtendedParentAlias
{
    public const VERSION = '1.2.2';
    public const VERSION_PARSEDOWN_REQUIRED = '1.7.4';
    public const VERSION_PARSEDOWN_EXTRA_REQUIRED = '0.8.1';
    public const MIN_PHP_VERSION = '7.4';

    private const TOC_TAG_DEFAULT = '[toc]';
    private const TOC_ID_ATTRIBUTE_DEFAULT = 'toc';
    private array $anchorDuplicates = [];
    private array $contentsListArray = [];
    private int $firstHeadLevel = 0;
    private string $contentsListString = '';
    private string $id_toc = '';
    private string $tag_toc = '';
    private $createAnchorIDCallback = null;


    private bool $legacyMode = false;
    private array $settings;

    // Standard settings
    private array $defaultSettings = [
        'abbreviations' => [ // Requires ParsedownExtra
            'enabled' => true,
            'allow_custom_abbr' => true,
            'predefine' => [],
        ],
        'code' => [
            'enabled' => true,
            'blocks' => true,
            'inline' => true,
        ],
        'comments' => true,
        'definition_lists' => true,
        'diagrams' => [
            'enabled' => false,
            'chartjs' => true,
            'mermaid' => true,
        ],
        'emojis' => true,
        'emphasis' => [
            'enabled' => true,
            'bold' => true,
            'italic' => true,
            'strikethroughs' => true,
            'insertions' => true,
            'subscript' => false,
            'superscript' => false,
            'keystrokes' => true,
            'marking' => true,
        ],
        'footnotes' => true,
        'headings' => [
            'enabled' => true,
            'allowed' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
            'auto_anchors' => [
                'enabled' => true,
                'delimiter' => '-',
                'lowercase' => true,
                'replacements' => [],
                'transliterate' => false,
                'blacklist' => [],
            ],
        ],
        'images' => true,
        'links' => [
            'enabled' => true,
            'email_links' => true,
        ],
        'lists' => [
            'enabled' => true,
            'tasks' => true,
        ],
        'markup' => true,
        'math' => [
            'enabled' => false,
            'inline' => [
                'enabled' => true,
                'delimiters' => [
                    ['left' => '\\(', 'right' => '\\)'],
                ],
            ],
            'block' => [
                'enabled' => true,
                'delimiters' => [
                    ['left' => '$$', 'right' => '$$'],
                    ['left' => '\\begin{equation}', 'right' => '\\end{equation}'],
                    ['left' => '\\begin{align}', 'right' => '\\end{align}'],
                    ['left' => '\\begin{alignat}', 'right' => '\\end{alignat}'],
                    ['left' => '\\begin{gather}', 'right' => '\\end{gather}'],
                    ['left' => '\\begin{CD}', 'right' => '\\end{CD}'],
                    ['left' => '\\[', 'right' => '\\]'],
                ],
            ],
        ],
        'quotes' => true,
        'references' => true,
        'smarty' => [
            'enabled' => false,
            'smart_angled_quotes' => true,
            'smart_backticks' => true,
            'smart_dashes' => true,
            'smart_ellipses' => true,
            'smart_quotes' => true,
            'substitutions' => [
                'ellipses' => '&hellip;',
                'left-angle-quote' => '&laquo;',
                'left-double-quote' => '&ldquo;',
                'left-single-quote' => '&lsquo;',
                'mdash' => '&mdash;',
                'ndash' => '&ndash;',
                'right-angle-quote' => '&raquo;',
                'right-double-quote' => '&rdquo;',
                'right-single-quote' => '&rsquo;',
            ],
        ],
        'special_attributes' => true,
        'tables' => [
            'enabled' => true,
            'tablespan' => true,
        ],
        'thematic_breaks' => true,
        'toc' => [
            'enabled' => true,
            'headings' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
            'set_toc_tag' => '[toc]',
        ],
        'typographer' => true,
    ];

    public function __construct(array $userSettings = [])
    {
        // Check if PHP version is supported
        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION) < 0) {
            $msg_error  = 'Version Error.' . PHP_EOL;
            $msg_error .= '  ParsedownExtended requires PHP version ' . self::MIN_PHP_VERSION . ' or later.' . PHP_EOL;
            $msg_error .= '  - Current version : ' . PHP_VERSION . PHP_EOL;
            $msg_error .= '  - Required version: ' . self::MIN_PHP_VERSION . PHP_EOL;
            throw new Exception($msg_error);
        }

        // Check if Parsedown is installed
        if (version_compare(\Parsedown::version, self::VERSION_PARSEDOWN_REQUIRED) < 0) {
            $msg_error  = 'Version Error.' . PHP_EOL;
            $msg_error .= '  ParsedownExtended requires a later version of Parsedown.' . PHP_EOL;
            $msg_error .= '  - Current version : ' . \Parsedown::version . PHP_EOL;
            $msg_error .= '  - Required version: ' . self::VERSION_PARSEDOWN_REQUIRED .' and later'. PHP_EOL;
            throw new Exception($msg_error);
        }

        // If ParsedownExtra is installed, check its version
        if (class_exists('ParsedownExtra')) {
            if (version_compare(\ParsedownExtra::version, self::VERSION_PARSEDOWN_EXTRA_REQUIRED) < 0) {
                $msg_error  = 'Version Error.' . PHP_EOL;
                $msg_error .= '  ParsedownExtended requires a later version of ParsedownExtra.' . PHP_EOL;
                $msg_error .= '  - Current version : ' . \ParsedownExtra::version . PHP_EOL;
                $msg_error .= '  - Required version: ' . self::VERSION_PARSEDOWN_EXTRA_REQUIRED .' and later'. PHP_EOL;
                throw new Exception($msg_error);
            }

            // Get parent constructor
            parent::__construct();

        }

        // Check if Parsedown 1.7.4 or later is installed but not 1.8
        if (version_compare(\Parsedown::version, '1.8.0') < 0 && version_compare(\Parsedown::version, '1.7.4') >= 0) {
            // set legacy mode to true
            $this->legacyMode = true;
        }

        $specialCharacters = [
            '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!', '|', '?', '"', "'", '<',
        ];

        $this->settings = $this->defaultSettings; // Start with default settings
        $this->initializeSettings($userSettings);

        // Add inline types
        $this->addInlineType('=', 'Marking');
        $this->addInlineType('+', 'Insertions');
        $this->addInlineType('[', 'Keystrokes');
        $this->addInlineType(['\\', '$'], 'MathNotation');
        $this->addInlineType('^', 'Superscript');
        $this->addInlineType('~', 'Subscript');
        $this->addInlineType(':', 'Emojis');
        $this->addInlineType(['<', '>', '-', '.', "'", '"', '`'], 'Smartypants');
        $this->addInlineType(['(','.','+','!','?'], 'Typographer');

        // Add block types
        $this->addBlockType(['\\','$'], 'MathNotation');


        /**
         * This function iterates through each inline type list and moves the 'SpecialCharacter' element
         * to the end of the list if it exists. This ensures that 'SpecialCharacter' is always processed last
         * when parsing the markdown content. This is necessary to prevent the parser from interfering with
         * other inline types.
         */
        foreach ($this->InlineTypes as &$list) {
            if (($key = array_search('SpecialCharacter', $list)) !== false) {
                unset($list[$key]);
                $list[] = 'SpecialCharacter'; // Append 'SpecialCharacter' at the end
            }
        }
    }

    /**
     * Initializes the settings of the ParsedownExtended class.
     *
     * @param array $userSettings The user-defined settings.
     *
     * @throws InvalidArgumentException If a non-existent setting is provided or if the provided value has an incorrect type.
     */
    private function initializeSettings(array $userSettings): void
    {
        foreach ($userSettings as $key => $value) {
            if (!isset($this->settings[$key])) {
                // Throw an error for non-existent setting
                throw new InvalidArgumentException("Setting '$key' does not exist.");
            }

            if (is_array($this->settings[$key])) {
                if (!is_array($value) && !is_bool($value)) {
                    // Throw an error for incorrect type for complex settings
                    throw new InvalidArgumentException("Invalid type for setting '$key'. Expected array or boolean.");
                }

                if (is_bool($value)) {
                    // Set the entire feature to the boolean value
                    $this->settings[$key]['enabled'] = $value;
                } else {
                    // Merge or replace the settings array
                    foreach ($value as $subKey => $subValue) {
                        if (!isset($this->settings[$key][$subKey])) {
                            // Throw an error for non-existent sub-setting
                            throw new InvalidArgumentException("Sub-setting '$subKey' does not exist in '$key'.");
                        }
                        // Optionally validate $subValue type here
                        $this->settings[$key][$subKey] = $subValue;
                    }
                }
            } else {
                if (!is_bool($value)) {
                    // Throw an error for incorrect type for simple settings
                    throw new InvalidArgumentException("Invalid type for setting '$key'. Expected boolean.");
                }
                $this->settings[$key] = $value;
            }
        }
    }

    // Inline types
    // -------------------------------------------------------------------------

    protected function inlineCode($Excerpt)
    {
        if ($this->getSetting('code') && $this->getSetting('code.inline')) {
            return parent::inlineCode($Excerpt);
        }
    }


    protected function inlineEmailTag($Excerpt)
    {
        if ($this->getSetting('links') && $this->getSetting('links.email_links')) {
            return parent::inlineEmailTag($Excerpt);
        }
    }

    protected function inlineImage($Excerpt)
    {
        if ($this->getSetting('images')) {
            return parent::inlineImage($Excerpt);
        }
    }

    protected function inlineLink($Excerpt)
    {
        if ($this->getSetting('links')) {
            return parent::inlineLink($Excerpt);
        }
    }

    protected function inlineMarkup($Excerpt)
    {
        if ($this->getSetting('markup')) {
            return parent::inlineMarkup($Excerpt);
        }
    }

    protected function inlineStrikethrough($Excerpt)
    {
        if ($this->getSetting('emphasis.strikethroughs') && $this->getSetting('emphasis')) {
            return parent::inlineStrikethrough($Excerpt);
        }
    }

    protected function inlineUrl($Excerpt)
    {
        if ($this->getSetting('links')) {
            return parent::inlineUrl($Excerpt);
        }
    }

    protected function inlineUrlTag($Excerpt)
    {
        if ($this->getSetting('links')) {
            return parent::inlineUrlTag($Excerpt);
        }
    }

    /**
     * Overwrites inlineEmphasis in order to give more control over
     * which emphasis elements are parsed.
     *
     * @param array $Excerpt The excerpt containing the text to parse.
     *
     * @return (int|string[])[]|null
     *
     * @psalm-return array{extent: int<0, max>, element: array{name: 'em'|'strong', handler: 'line', text: string}}|null
     */
    protected function inlineEmphasis($Excerpt)
    {
        if (!$this->getSetting('emphasis') || !isset($Excerpt['text'][1])) {
            return;
        }

        $marker = $Excerpt['text'][0];

        // Check if the emphasis bold is enabled
        if ($this->getSetting('emphasis.bold') and preg_match($this->StrongRegex[$marker], $Excerpt['text'], $matches)) {
            $emphasis = 'strong';
        } elseif ($this->getSetting('emphasis.italic') and preg_match($this->EmRegex[$marker], $Excerpt['text'], $matches)) {
            $emphasis = 'em';
        } else {
            return;
        }

        return [
            'extent' => strlen($matches[0]),
            'element' => [
                'name' => $emphasis,
                'handler' => 'line',
                'text' => $matches[1],
            ],
        ];
    }


    /**
     * Inline Marking function.
     *
     * This function is responsible for handling inline mark in the parsed text.
     * It searches for text enclosed in double equal signs (==) and wraps it in a <mark> element.
     *
     * @param array $Excerpt The excerpt containing the text to be highlighted.
     *
     * @return (int|string[])[]|null
     *
     * @psalm-return array{extent: int<0, max>, element: array{name: 'mark', text: string}}|null
     */
    protected function inlineMarking(array $Excerpt): ?array
    {
        if (!$this->getSetting('emphasis.marking') || !$this->getSetting('emphasis')) {
            return null;
        }

        if (preg_match('/^==((?:\\\\\=|[^=]|=[^=]*=)+?)==(?!=)/s', $Excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'mark',
                    'text' => $matches[1],
                ],
            ];
        }

        return null;
    }

    /**
     * @return (int|string[])[]|null
     *
     * @psalm-return array{extent: int<0, max>, element: array{name: 'ins', text: string}}|null
     */
    protected function inlineInsertions(array $Excerpt): ?array
    {
        if (!$this->getSetting('emphasis.insertions') || !$this->getSetting('emphasis')) {
            return null;
        }

        if (preg_match('/^\+\+((?:\\\\\+|[^\+]|\+[^\+]*\+)+?)\+\+(?!\+)/s', $Excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'ins',
                    'text' => $matches[1],
                ],
            ];
        }

        return null;
    }

    /**
     * Parses inline keystrokes in the given excerpt.
     * It searches for text enclosed in double square brackets (e.g. [[Ctrl+Alt+Del]]) and wraps it in a <kbd> element.
     *
     * @param array $Excerpt The excerpt to parse.
     *
     * @return (int|string[])[]|null
     *
     * @psalm-return array{extent: int<0, max>, element: array{name: 'kbd', text: string}}|null
     */
    protected function inlineKeystrokes(array $Excerpt): ?array
    {
        if (!$this->getSetting('emphasis.keystrokes') || !$this->getSetting('emphasis')) {
            return null;
        }

        if (preg_match('/^(?<!\[)(?:\[\[([^\[\]]*|[\[\]])\]\])(?!\])/s', $Excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'kbd',
                    'text' => $matches[1],
                ],
            ];
        }

        return null;
    }


    /**
     * Parses inline superscript elements in the given excerpt.
     * It searches for text enclosed in double carets (e.g. ^superscript^) and wraps it in a <sup> element.
     *
     * @param array $Excerpt The excerpt to parse.
     *
     * @return (int|string[])[]|null
     *
     * @psalm-return array{extent: int<0, max>, element: array{name: 'sup', text: string, function: 'lineElements'}}|null
     */
    protected function inlineSuperscript(array $Excerpt): ?array
    {
        if (!$this->getSetting('emphasis.superscript') || !$this->getSetting('emphasis')) {
            return null;
        }

        if (preg_match('/^[\^]((?:\\\\\\^|[^\^]|[\^][^\^]+?[\^][\^])+?)[\^](?![\^])/s', $Excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'sup',
                    'text' => $matches[1],
                    'function' => 'lineElements',
                ],
            ];
        }

        return null;
    }


    /**
     * Parses inline subscript elements in the given excerpt.
     * It searches for text enclosed in double tildes (e.g. ~subscript~) and wraps it in a <sub> element.
     *
     * @param array $Excerpt The excerpt to parse.
     *
     * @return (int|string[])[]|null
     *
     * @psalm-return array{extent: int<0, max>, element: array{name: 'sub', text: string, function: 'lineElements'}}|null
     */
    protected function inlineSubscript(array $Excerpt): ?array
    {
        if (!$this->getSetting('emphasis.subscript') || !$this->getSetting('emphasis')) {
            return null;
        }

        if (preg_match('/^~((?:\\\\~|[^~]|~~[^~]*~~)+?)~(?!~)/s', $Excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'sub',
                    'text' => $matches[1],
                    'function' => 'lineElements',
                ],
            ];
        }

        return null;
    }


    /**
     * Parses inline math notation in the given excerpt.
     *
     * @param array $Excerpt The excerpt to parse.
     *
     * @return (int|string[])[]|null
     *
     * @psalm-return array{extent: int<0, max>, element: array{text: string}}|null
     */
    protected function inlineMathNotation($Excerpt)
    {
        if (!$this->getSetting('math') || !$this->getSetting('math.inline')) {
            return null;
        }

        if (!isset($Excerpt['text'][1])) {
            return;
        }

        if ($Excerpt['before'] !== '' && preg_match('/\s/', $Excerpt['before']) === 0) {
            return;
        }

        foreach ($this->settings['math']['inline']['delimiters'] as $config) {
            $leftMarker = preg_quote($config['left'], '/');
            $rightMarker = preg_quote($config['right'], '/');

            if ($config['left'][0] === '\\' || strlen($config['left']) > 1) {
                $regex = '/^(?<!\S)' . $leftMarker . '(?![\r\n])((?:\\\\' . $rightMarker . '|\\\\' . $leftMarker . '|[^\r\n])+?)' . $rightMarker . '(?![^\s,.])/s';
            } else {
                $regex = '/^(?<!\S)' . $leftMarker . '(?![\r\n])((?:\\\\' . $rightMarker . '|\\\\' . $leftMarker . '|[^' . $rightMarker . '\r\n])+?)' . $rightMarker . '(?![^\s,.])/s';
            }

            if (preg_match($regex, $Excerpt['text'], $matches)) {
                return [
                    'extent' => strlen($matches[0]),
                    'element' => [
                        'text' => $matches[0],
                    ],
                ];
            }
        }

        return;
    }


    /**
     * Escapes inline escape sequences in the parsedown text.
     *
     * @param array $Excerpt The excerpt containing the escape sequence.
     *
     * @return (int|mixed)[]|null
     *
     * @psalm-return array{markup: mixed, extent: 2}|null
     */
    protected function inlineEscapeSequence($Excerpt)
    {

        if ($this->settings['math']) {
            foreach ($this->settings['math']['inline']['delimiters'] as $config) {

                $leftMarker = preg_quote($config['left'], '/');
                $rightMarker = preg_quote($config['right'], '/');

                if ($config['left'][0] === '\\' || strlen($config['left']) > 1) {
                    $regex = '/^(?<!\S)' . $leftMarker . '(?![\r\n])((?:\\\\' . $rightMarker . '|\\\\' . $leftMarker . '|[^\r\n])+?)' . $rightMarker . '(?![^\s,.])/s';
                } else {
                    $regex = '/^(?<!\S)' . $leftMarker . '(?![\r\n])((?:\\\\' . $rightMarker . '|\\\\' . $leftMarker . '|[^' . $rightMarker . '\r\n])+?)' . $rightMarker . '(?![^\s,.])/s';
                }

                if (preg_match($regex, $Excerpt['text'])) {
                    return;
                }
            }
        }

        if (isset($Excerpt['text'][1]) && in_array($Excerpt['text'][1], $this->specialCharacters)) {
            return [
                'markup' => $Excerpt['text'][1],
                'extent' => 2,
            ];
        }
    }


    /**
     * Applies typographic substitutions to the inline text.
     * It searches for typographic substitutions and replaces them with their HTML entities.
     *
     * @param array $Excerpt The excerpt containing the inline text.
     *
     * @return ((null|string)[]|int)[]|null
     *
     * @psalm-return array{extent: int<0, max>, element: array{text: null|string}}|null
     */
    protected function inlineTypographer(array $Excerpt): ?array
    {
        if (!$this->getSetting('typographer')) {
            return null;
        }

        // Check if smartypants and smart ellipses settings are enabled
        $ellipses = $this->getSetting('smarty') && $this->getSetting('smarty.smart_ellipses') ? html_entity_decode($this->getSetting('smarty.substitutions.ellipses')) : '...';

        $substitutions = [
            '/\(c\)/i' => html_entity_decode('&copy;'),
            '/\(r\)/i' => html_entity_decode('&reg;'),
            '/\(tm\)/i' => html_entity_decode('&trade;'),
            '/\(p\)/i' => html_entity_decode('&para;'),
            '/\+-/i' => html_entity_decode('&plusmn;'),
            '/\!\.{3,}/i' => '!..',
            '/\?\.{3,}/i' => '?..',
            '/\.{4,}/i' => $ellipses,
            '/(?<![\.!?])(\.{2})(?!\.)/i' => $ellipses,

        ];

        if (preg_match('/\+-|\(p\)|\(tm\)|\(r\)|\(c\)|\.{2,}|\!\.{3,}|\?\.{3,}/i', $Excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'text' => preg_replace(array_keys($substitutions), array_values($substitutions), $matches[0]),
                ],
            ];
        }
        return null;
    }

    /**
     * Applies smartypants substitutions to the inline text.
     *
     * @param array $Excerpt The excerpt containing the inline text.
     *
     * @return (int|string[])[]|null
     *
     * @psalm-return array{extent: int<1, max>, element: array{text: string}}|null
     */
    protected function inlineSmartypants($Excerpt)
    {
        if (!$this->getSetting('smarty')) {
            return null;
        }

        // Substitutions
        $backtickDoublequoteOpen = $this->getSetting('smarty.substitutions.left-double-quote');
        $backtickDoublequoteClose = $this->getSetting('smarty.substitutions.right-double-quote');
        $smartDoublequoteOpen = $this->getSetting('smarty.substitutions.left-double-quote');
        $smartDoublequoteClose = $this->getSetting('smarty.substitutions.right-double-quote');
        $smartSinglequoteOpen = $this->getSetting('smarty.substitutions.left-single-quote');
        $smartSinglequoteClose = $this->getSetting('smarty.substitutions.right-single-quote');
        $leftAngleQuote = $this->getSetting('smarty.substitutions.left-angle-quote');
        $rightAngleQuote = $this->getSetting('smarty.substitutions.right-angle-quote');

        if (!isset($Excerpt['before'])) {
            $Excerpt['before'] = '';
        }


        if (preg_match('/(``)(?!\s)([^"\'`]{1,})(\'\')|(\")(?!\s)([^\"]{1,})(\")|(\')(?!\s)([^\']{1,})(\')|(<{2})(?!\s)([^<>]{1,})(>{2})|(?<!\.)(\.{3})(?!\.)|(-{3})|(-{2})/i', $Excerpt['text'], $matches)) {
            $matches = array_values(array_filter($matches));

            // Smart backticks
            if ($this->getSetting('smarty.smart_backticks') && '``' === $matches[1]) {
                $length = strlen(trim($Excerpt['before']));
                if ($length > 0) {
                    return;
                }

                return [
                    'extent' => strlen($matches[0]),
                    'element' => [
                        'text' => html_entity_decode($backtickDoublequoteOpen).$matches[2].html_entity_decode($backtickDoublequoteClose),
                    ],
                ];
            }

            // Smart quotes
            if ($this->getSetting('smarty.smart_quotes')) {
                if ("'" === $matches[1]) {
                    $length = strlen(trim($Excerpt['before']));
                    if ($length > 0) {
                        return;
                    }

                    return [
                        'extent' => strlen($matches[0]),
                        'element' => [
                            'text' => html_entity_decode($smartSinglequoteOpen).$matches[2].html_entity_decode($smartSinglequoteClose),
                        ],
                    ];
                }

                if ('"' === $matches[1]) {
                    $length = strlen(trim($Excerpt['before']));
                    if ($length > 0) {
                        return;
                    }

                    return [
                        'extent' => strlen($matches[0]),
                        'element' => [
                            'text' => html_entity_decode($smartDoublequoteOpen).$matches[2].html_entity_decode($smartDoublequoteClose),
                        ],
                    ];
                }
            }

            // Smart angled quotes
            if ($this->getSetting('smarty.smart_angled_quotes') && '<<' === $matches[1]) {
                $length = strlen(trim($Excerpt['before']));
                if ($length > 0) {
                    return;
                }

                return [
                    'extent' => strlen($matches[0]),
                    'element' => [
                        'text' => html_entity_decode($leftAngleQuote).$matches[2].html_entity_decode($rightAngleQuote),
                    ],
                ];
            }

            // Smart dashes
            if ($this->getSetting('smarty.smart_dashes')) {
                if ('---' === $matches[1]) {
                    return [
                        'extent' => strlen($matches[0]),
                        'element' => [
                            'text' => html_entity_decode($this->getSetting('smarty.substitutions.mdash')),
                        ],
                    ];
                }

                if ('--' === $matches[1]) {
                    return [
                        'extent' => strlen($matches[0]),
                        'element' => [
                            'text' => html_entity_decode($this->getSetting('smarty.substitutions.ndash')),
                        ],
                    ];
                }
            }

            // Smart ellipses
            if ($this->getSetting('smarty.smart_ellipses') && '...' === $matches[1]) {
                return [
                    'extent' => strlen($matches[0]),
                    'element' => [
                        'text' => html_entity_decode($this->getSetting('smarty.substitutions.ellipses')),
                    ],
                ];
            }
        }
    }

    /**
     * Replaces emoji codes with corresponding emoji characters.
     *
     * @param string $Excerpt The text to process.
     *
     * @return (int|string[])[]|null
     *
     * @psalm-return array{extent: int<0, max>, element: array{text: string}}|null
     */
    protected function inlineEmojis(array $Excerpt): ?array
    {
        if (!$this->getSetting('emojis')) {
            return null;
        }

        $emojiMap = [
            ':smile:' => '😄', ':laughing:' => '😆', ':blush:' => '😊', ':smiley:' => '😃',
            ':relaxed:' => '☺️', ':smirk:' => '😏', ':heart_eyes:' => '😍', ':kissing_heart:' => '😘',
            ':kissing_closed_eyes:' => '😚', ':flushed:' => '😳', ':relieved:' => '😌', ':satisfied:' => '😆',
            ':grin:' => '😁', ':wink:' => '😉', ':stuck_out_tongue_winking_eye:' => '😜', ':stuck_out_tongue_closed_eyes:' => '😝',
            ':grinning:' => '😀', ':kissing:' => '😗', ':kissing_smiling_eyes:' => '😙', ':stuck_out_tongue:' => '😛',
            ':sleeping:' => '😴', ':worried:' => '😟', ':frowning:' => '😦', ':anguished:' => '😧',
            ':open_mouth:' => '😮', ':grimacing:' => '😬', ':confused:' => '😕', ':hushed:' => '😯',
            ':expressionless:' => '😑', ':unamused:' => '😒', ':sweat_smile:' => '😅', ':sweat:' => '😓',
            ':disappointed_relieved:' => '😥', ':weary:' => '😩', ':pensive:' => '😔', ':disappointed:' => '😞',
            ':confounded:' => '😖', ':fearful:' => '😨', ':cold_sweat:' => '😰', ':persevere:' => '😣',
            ':cry:' => '😢', ':sob:' => '😭', ':joy:' => '😂', ':astonished:' => '😲',
            ':scream:' => '😱', ':tired_face:' => '😫', ':angry:' => '😠', ':rage:' => '😡',
            ':triumph:' => '😤', ':sleepy:' => '😪', ':yum:' => '😋', ':mask:' => '😷',
            ':sunglasses:' => '😎', ':dizzy_face:' => '😵', ':imp:' => '👿', ':smiling_imp:' => '😈',
            ':neutral_face:' => '😐', ':no_mouth:' => '😶', ':innocent:' => '😇', ':alien:' => '👽',
            ':yellow_heart:' => '💛', ':blue_heart:' => '💙', ':purple_heart:' => '💜', ':heart:' => '❤️',
            ':green_heart:' => '💚', ':broken_heart:' => '💔', ':heartbeat:' => '💓', ':heartpulse:' => '💗',
            ':two_hearts:' => '💕', ':revolving_hearts:' => '💞', ':cupid:' => '💘', ':sparkling_heart:' => '💖',
            ':sparkles:' => '✨', ':star:' => '⭐️', ':star2:' => '🌟', ':dizzy:' => '💫',
            ':boom:' => '💥', ':collision:' => '💥', ':anger:' => '💢', ':exclamation:' => '❗️',
            ':question:' => '❓', ':grey_exclamation:' => '❕', ':grey_question:' => '❔', ':zzz:' => '💤',
            ':dash:' => '💨', ':sweat_drops:' => '💦', ':notes:' => '🎶', ':musical_note:' => '🎵',
            ':fire:' => '🔥', ':hankey:' => '💩', ':poop:' => '💩', ':shit:' => '💩',
            ':+1:' => '👍', ':thumbsup:' => '👍', ':-1:' => '👎', ':thumbsdown:' => '👎',
            ':ok_hand:' => '👌', ':punch:' => '👊', ':facepunch:' => '👊', ':fist:' => '✊',
            ':v:' => '✌️', ':wave:' => '👋', ':hand:' => '✋', ':raised_hand:' => '✋',
            ':open_hands:' => '👐', ':point_up:' => '☝️', ':point_down:' => '👇', ':point_left:' => '👈',
            ':point_right:' => '👉', ':raised_hands:' => '🙌', ':pray:' => '🙏', ':point_up_2:' => '👆',
            ':clap:' => '👏', ':muscle:' => '💪', ':metal:' => '🤘', ':fu:' => '🖕',
            ':walking:' => '🚶', ':runner:' => '🏃', ':running:' => '🏃', ':couple:' => '👫',
            ':family:' => '👪', ':two_men_holding_hands:' => '👬', ':two_women_holding_hands:' => '👭', ':dancer:' => '💃',
            ':dancers:' => '👯', ':ok_woman:' => '🙆', ':no_good:' => '🙅', ':information_desk_person:' => '💁',
            ':raising_hand:' => '🙋', ':bride_with_veil:' => '👰', ':person_with_pouting_face:' => '🙎', ':person_frowning:' => '🙍',
            ':bow:' => '🙇', ':couple_with_heart:' => '💑', ':massage:' => '💆', ':haircut:' => '💇',
            ':nail_care:' => '💅', ':boy:' => '👦', ':girl:' => '👧', ':woman:' => '👩',
            ':man:' => '👨', ':baby:' => '👶', ':older_woman:' => '👵', ':older_man:' => '👴',
            ':person_with_blond_hair:' => '👱', ':man_with_gua_pi_mao:' => '👲', ':man_with_turban:' => '👳', ':construction_worker:' => '👷',
            ':cop:' => '👮', ':angel:' => '👼', ':princess:' => '👸', ':smiley_cat:' => '😺',
            ':smile_cat:' => '😸', ':heart_eyes_cat:' => '😻', ':kissing_cat:' => '😽', ':smirk_cat:' => '😼',
            ':scream_cat:' => '🙀', ':crying_cat_face:' => '😿', ':joy_cat:' => '😹', ':pouting_cat:' => '😾',
            ':japanese_ogre:' => '👹', ':japanese_goblin:' => '👺', ':see_no_evil:' => '🙈', ':hear_no_evil:' => '🙉',
            ':speak_no_evil:' => '🙊', ':guardsman:' => '💂', ':skull:' => '💀', ':feet:' => '🐾',
            ':lips:' => '👄', ':kiss:' => '💋', ':droplet:' => '💧', ':ear:' => '👂',
            ':eyes:' => '👀', ':nose:' => '👃', ':tongue:' => '👅', ':love_letter:' => '💌',
            ':bust_in_silhouette:' => '👤', ':busts_in_silhouette:' => '👥', ':speech_balloon:' => '💬', ':thought_balloon:' => '💭',
            ':sunny:' => '☀️', ':umbrella:' => '☔️', ':cloud:' => '☁️', ':snowflake:' => '❄️',
            ':snowman:' => '⛄️', ':zap:' => '⚡️', ':cyclone:' => '🌀', ':foggy:' => '🌁',
            ':ocean:' => '🌊', ':cat:' => '🐱', ':dog:' => '🐶', ':mouse:' => '🐭',
            ':hamster:' => '🐹', ':rabbit:' => '🐰', ':wolf:' => '🐺', ':frog:' => '🐸',
            ':tiger:' => '🐯', ':koala:' => '🐨', ':bear:' => '🐻', ':pig:' => '🐷',
            ':pig_nose:' => '🐽', ':cow:' => '🐮', ':boar:' => '🐗', ':monkey_face:' => '🐵',
            ':monkey:' => '🐒', ':horse:' => '🐴', ':racehorse:' => '🐎', ':camel:' => '🐫',
            ':sheep:' => '🐑', ':elephant:' => '🐘', ':panda_face:' => '🐼', ':snake:' => '🐍',
            ':bird:' => '🐦', ':baby_chick:' => '🐤', ':hatched_chick:' => '🐥', ':hatching_chick:' => '🐣',
            ':chicken:' => '🐔', ':penguin:' => '🐧', ':turtle:' => '🐢', ':bug:' => '🐛',
            ':honeybee:' => '🐝', ':ant:' => '🐜', ':beetle:' => '🐞', ':snail:' => '🐌',
            ':octopus:' => '🐙', ':tropical_fish:' => '🐠', ':fish:' => '🐟', ':whale:' => '🐳',
            ':whale2:' => '🐋', ':dolphin:' => '🐬', ':cow2:' => '🐄', ':ram:' => '🐏',
            ':rat:' => '🐀', ':water_buffalo:' => '🐃', ':tiger2:' => '🐅', ':rabbit2:' => '🐇',
            ':dragon:' => '🐉', ':goat:' => '🐐', ':rooster:' => '🐓', ':dog2:' => '🐕',
            ':pig2:' => '🐖', ':mouse2:' => '🐁', ':ox:' => '🐂', ':dragon_face:' => '🐲',
            ':blowfish:' => '🐡', ':crocodile:' => '🐊', ':dromedary_camel:' => '🐪', ':leopard:' => '🐆',
            ':cat2:' => '🐈', ':poodle:' => '🐩', ':crab' => '🦀', ':paw_prints:' => '🐾', ':bouquet:' => '💐',
            ':cherry_blossom:' => '🌸', ':tulip:' => '🌷', ':four_leaf_clover:' => '🍀', ':rose:' => '🌹',
            ':sunflower:' => '🌻', ':hibiscus:' => '🌺', ':maple_leaf:' => '🍁', ':leaves:' => '🍃',
            ':fallen_leaf:' => '🍂', ':herb:' => '🌿', ':mushroom:' => '🍄', ':cactus:' => '🌵',
            ':palm_tree:' => '🌴', ':evergreen_tree:' => '🌲', ':deciduous_tree:' => '🌳', ':chestnut:' => '🌰',
            ':seedling:' => '🌱', ':blossom:' => '🌼', ':ear_of_rice:' => '🌾', ':shell:' => '🐚',
            ':globe_with_meridians:' => '🌐', ':sun_with_face:' => '🌞', ':full_moon_with_face:' => '🌝', ':new_moon_with_face:' => '🌚',
            ':new_moon:' => '🌑', ':waxing_crescent_moon:' => '🌒', ':first_quarter_moon:' => '🌓', ':waxing_gibbous_moon:' => '🌔',
            ':full_moon:' => '🌕', ':waning_gibbous_moon:' => '🌖', ':last_quarter_moon:' => '🌗', ':waning_crescent_moon:' => '🌘',
            ':last_quarter_moon_with_face:' => '🌜', ':first_quarter_moon_with_face:' => '🌛', ':moon:' => '🌔', ':earth_africa:' => '🌍',
            ':earth_americas:' => '🌎', ':earth_asia:' => '🌏', ':volcano:' => '🌋', ':milky_way:' => '🌌',
            ':partly_sunny:' => '⛅️', ':bamboo:' => '🎍', ':gift_heart:' => '💝', ':dolls:' => '🎎',
            ':school_satchel:' => '🎒', ':mortar_board:' => '🎓', ':flags:' => '🎏', ':fireworks:' => '🎆',
            ':sparkler:' => '🎇', ':wind_chime:' => '🎐', ':rice_scene:' => '🎑', ':jack_o_lantern:' => '🎃',
            ':ghost:' => '👻', ':santa:' => '🎅', ':christmas_tree:' => '🎄', ':gift:' => '🎁',
            ':bell:' => '🔔', ':no_bell:' => '🔕', ':tanabata_tree:' => '🎋', ':tada:' => '🎉',
            ':confetti_ball:' => '🎊', ':balloon:' => '🎈', ':crystal_ball:' => '🔮', ':cd:' => '💿',
            ':dvd:' => '📀', ':floppy_disk:' => '💾', ':camera:' => '📷', ':video_camera:' => '📹',
            ':movie_camera:' => '🎥', ':computer:' => '💻', ':tv:' => '📺', ':iphone:' => '📱',
            ':phone:' => '☎️', ':telephone:' => '☎️', ':telephone_receiver:' => '📞', ':pager:' => '📟',
            ':fax:' => '📠', ':minidisc:' => '💽', ':vhs:' => '📼', ':sound:' => '🔉',
            ':speaker:' => '🔈', ':mute:' => '🔇', ':loudspeaker:' => '📢', ':mega:' => '📣',
            ':hourglass:' => '⌛️', ':hourglass_flowing_sand:' => '⏳', ':alarm_clock:' => '⏰', ':watch:' => '⌚️',
            ':radio:' => '📻', ':satellite:' => '📡', ':loop:' => '➿', ':mag:' => '🔍',
            ':mag_right:' => '🔎', ':unlock:' => '🔓', ':lock:' => '🔒', ':lock_with_ink_pen:' => '🔏',
            ':closed_lock_with_key:' => '🔐', ':key:' => '🔑', ':bulb:' => '💡', ':flashlight:' => '🔦',
            ':high_brightness:' => '🔆', ':low_brightness:' => '🔅', ':electric_plug:' => '🔌', ':battery:' => '🔋',
            ':calling:' => '📲', ':email:' => '✉️', ':mailbox:' => '📫', ':postbox:' => '📮',
            ':bath:' => '🛀', ':bathtub:' => '🛁', ':shower:' => '🚿', ':toilet:' => '🚽',
            ':wrench:' => '🔧', ':nut_and_bolt:' => '🔩', ':hammer:' => '🔨', ':seat:' => '💺',
            ':moneybag:' => '💰', ':yen:' => '💴', ':dollar:' => '💵', ':pound:' => '💷',
            ':euro:' => '💶', ':credit_card:' => '💳', ':money_with_wings:' => '💸', ':e-mail:' => '📧',
            ':inbox_tray:' => '📥', ':outbox_tray:' => '📤', ':envelope:' => '✉️', ':incoming_envelope:' => '📨',
            ':postal_horn:' => '📯', ':mailbox_closed:' => '📪', ':mailbox_with_mail:' => '📬', ':mailbox_with_no_mail:' => '📭',
            ':door:' => '🚪', ':smoking:' => '🚬', ':bomb:' => '💣', ':gun:' => '🔫',
            ':hocho:' => '🔪', ':pill:' => '💊', ':syringe:' => '💉', ':page_facing_up:' => '📄',
            ':page_with_curl:' => '📃', ':bookmark_tabs:' => '📑', ':bar_chart:' => '📊', ':chart_with_upwards_trend:' => '📈',
            ':chart_with_downwards_trend:' => '📉', ':scroll:' => '📜', ':clipboard:' => '📋', ':calendar:' => '📆',
            ':date:' => '📅', ':card_index:' => '📇', ':file_folder:' => '📁', ':open_file_folder:' => '📂',
            ':scissors:' => '✂️', ':pushpin:' => '📌', ':paperclip:' => '📎', ':black_nib:' => '✒️',
            ':pencil2:' => '✏️', ':straight_ruler:' => '📏', ':triangular_ruler:' => '📐', ':closed_book:' => '📕',
            ':green_book:' => '📗', ':blue_book:' => '📘', ':orange_book:' => '📙', ':notebook:' => '📓',
            ':notebook_with_decorative_cover:' => '📔', ':ledger:' => '📒', ':books:' => '📚', ':bookmark:' => '🔖',
            ':name_badge:' => '📛', ':microscope:' => '🔬', ':telescope:' => '🔭', ':newspaper:' => '📰',
            ':football:' => '🏈', ':basketball:' => '🏀', ':soccer:' => '⚽️', ':baseball:' => '⚾️',
            ':tennis:' => '🎾', ':8ball:' => '🎱', ':rugby_football:' => '🏉', ':bowling:' => '🎳',
            ':golf:' => '⛳️', ':mountain_bicyclist:' => '🚵', ':bicyclist:' => '🚴', ':horse_racing:' => '🏇',
            ':snowboarder:' => '🏂', ':swimmer:' => '🏊', ':surfer:' => '🏄', ':ski:' => '🎿',
            ':spades:' => '♠️', ':hearts:' => '♥️', ':clubs:' => '♣️', ':diamonds:' => '♦️',
            ':gem:' => '💎', ':ring:' => '💍', ':trophy:' => '🏆', ':musical_score:' => '🎼',
            ':musical_keyboard:' => '🎹', ':violin:' => '🎻', ':space_invader:' => '👾', ':video_game:' => '🎮',
            ':black_joker:' => '🃏', ':flower_playing_cards:' => '🎴', ':game_die:' => '🎲', ':dart:' => '🎯',
            ':mahjong:' => '🀄️', ':clapper:' => '🎬', ':memo:' => '📝', ':pencil:' => '📝',
            ':book:' => '📖', ':art:' => '🎨', ':microphone:' => '🎤', ':headphones:' => '🎧',
            ':trumpet:' => '🎺', ':saxophone:' => '🎷', ':guitar:' => '🎸', ':shoe:' => '👞',
            ':sandal:' => '👡', ':high_heel:' => '👠', ':lipstick:' => '💄', ':boot:' => '👢',
            ':shirt:' => '👕', ':tshirt:' => '👕', ':necktie:' => '👔', ':womans_clothes:' => '👚',
            ':dress:' => '👗', ':running_shirt_with_sash:' => '🎽', ':jeans:' => '👖', ':kimono:' => '👘',
            ':bikini:' => '👙', ':ribbon:' => '🎀', ':tophat:' => '🎩', ':crown:' => '👑',
            ':womans_hat:' => '👒', ':mans_shoe:' => '👞', ':closed_umbrella:' => '🌂', ':briefcase:' => '💼',
            ':handbag:' => '👜', ':pouch:' => '👝', ':purse:' => '👛', ':eyeglasses:' => '👓',
            ':fishing_pole_and_fish:' => '🎣', ':coffee:' => '☕️', ':tea:' => '🍵', ':sake:' => '🍶',
            ':baby_bottle:' => '🍼', ':beer:' => '🍺', ':beers:' => '🍻', ':cocktail:' => '🍸',
            ':tropical_drink:' => '🍹', ':wine_glass:' => '🍷', ':fork_and_knife:' => '🍴', ':pizza:' => '🍕',
            ':hamburger:' => '🍔', ':fries:' => '🍟', ':poultry_leg:' => '🍗', ':meat_on_bone:' => '🍖',
            ':spaghetti:' => '🍝', ':curry:' => '🍛', ':fried_shrimp:' => '🍤', ':bento:' => '🍱',
            ':sushi:' => '🍣', ':fish_cake:' => '🍥', ':rice_ball:' => '🍙', ':rice_cracker:' => '🍘',
            ':rice:' => '🍚', ':ramen:' => '🍜', ':stew:' => '🍲', ':oden:' => '🍢',
            ':dango:' => '🍡', ':egg:' => '🥚', ':bread:' => '🍞', ':doughnut:' => '🍩',
            ':custard:' => '🍮', ':icecream:' => '🍦', ':ice_cream:' => '🍨', ':shaved_ice:' => '🍧',
            ':birthday:' => '🎂', ':cake:' => '🍰', ':cookie:' => '🍪', ':chocolate_bar:' => '🍫',
            ':candy:' => '🍬', ':lollipop:' => '🍭', ':honey_pot:' => '🍯', ':apple:' => '🍎',
            ':green_apple:' => '🍏', ':tangerine:' => '🍊', ':lemon:' => '🍋', ':cherries:' => '🍒',
            ':grapes:' => '🍇', ':watermelon:' => '🍉', ':strawberry:' => '🍓', ':peach:' => '🍑',
            ':melon:' => '🍈', ':banana:' => '🍌', ':pear:' => '🍐', ':pineapple:' => '🍍',
            ':sweet_potato:' => '🍠', ':eggplant:' => '🍆', ':tomato:' => '🍅', ':corn:' => '🌽',
            ':house:' => '🏠', ':house_with_garden:' => '🏡', ':school:' => '🏫', ':office:' => '🏢',
            ':post_office:' => '🏣', ':hospital:' => '🏥', ':bank:' => '🏦', ':convenience_store:' => '🏪',
            ':love_hotel:' => '🏩', ':hotel:' => '🏨', ':wedding:' => '💒', ':church:' => '⛪️',
            ':department_store:' => '🏬', ':european_post_office:' => '🏤', ':city_sunrise:' => '🌇', ':city_sunset:' => '🌆',
            ':japanese_castle:' => '🏯', ':european_castle:' => '🏰', ':tent:' => '⛺️', ':factory:' => '🏭',
            ':tokyo_tower:' => '🗼', ':japan:' => '🗾', ':mount_fuji:' => '🗻', ':sunrise_over_mountains:' => '🌄',
            ':sunrise:' => '🌅', ':stars:' => '🌠', ':statue_of_liberty:' => '🗽', ':bridge_at_night:' => '🌉',
            ':carousel_horse:' => '🎠', ':rainbow:' => '🌈', ':ferris_wheel:' => '🎡', ':fountain:' => '⛲️',
            ':roller_coaster:' => '🎢', ':ship:' => '🚢', ':speedboat:' => '🚤', ':boat:' => '⛵️',
            ':sailboat:' => '⛵️', ':rowboat:' => '🚣', ':anchor:' => '⚓️', ':rocket:' => '🚀',
            ':airplane:' => '✈️', ':helicopter:' => '🚁', ':steam_locomotive:' => '🚂', ':tram:' => '🚊',
            ':mountain_railway:' => '🚞', ':bike:' => '🚲', ':aerial_tramway:' => '🚡', ':suspension_railway:' => '🚟',
            ':mountain_cableway:' => '🚠', ':tractor:' => '🚜', ':blue_car:' => '🚙', ':oncoming_automobile:' => '🚘',
            ':car:' => '🚗', ':red_car:' => '🚗', ':taxi:' => '🚕', ':oncoming_taxi:' => '🚖',
            ':articulated_lorry:' => '🚛', ':bus:' => '🚌', ':oncoming_bus:' => '🚍', ':rotating_light:' => '🚨',
            ':police_car:' => '🚓', ':oncoming_police_car:' => '🚔', ':fire_engine:' => '🚒', ':ambulance:' => '🚑',
            ':minibus:' => '🚐', ':truck:' => '🚚', ':train:' => '🚋', ':station:' => '🚉',
            ':train2:' => '🚆', ':bullettrain_front:' => '🚅', ':bullettrain_side:' => '🚄', ':light_rail:' => '🚈',
            ':monorail:' => '🚝', ':railway_car:' => '🚃', ':trolleybus:' => '🚎', ':ticket:' => '🎫',
            ':fuelpump:' => '⛽️', ':vertical_traffic_light:' => '🚦', ':traffic_light:' => '🚥', ':warning:' => '⚠️',
            ':construction:' => '🚧', ':beginner:' => '🔰', ':atm:' => '🏧', ':slot_machine:' => '🎰',
            ':busstop:' => '🚏', ':barber:' => '💈', ':hotsprings:' => '♨️', ':checkered_flag:' => '🏁',
            ':crossed_flags:' => '🎌', ':izakaya_lantern:' => '🏮', ':moyai:' => '🗿', ':circus_tent:' => '🎪',
            ':performing_arts:' => '🎭', ':round_pushpin:' => '📍', ':triangular_flag_on_post:' => '🚩', ':jp:' => '🇯🇵',
            ':kr:' => '🇰🇷', ':cn:' => '🇨🇳', ':us:' => '🇺🇸', ':fr:' => '🇫🇷',
            ':es:' => '🇪🇸', ':it:' => '🇮🇹', ':ru:' => '🇷🇺', ':gb:' => '🇬🇧',
            ':uk:' => '🇬🇧', ':de:' => '🇩🇪', ':one:' => '1️⃣', ':two:' => '2️⃣',
            ':three:' => '3️⃣', ':four:' => '4️⃣', ':five:' => '5️⃣', ':six:' => '6️⃣',
            ':seven:' => '7️⃣', ':eight:' => '8️⃣', ':nine:' => '9️⃣', ':keycap_ten:' => '🔟',
            ':1234:' => '🔢', ':zero:' => '0️⃣', ':hash:' => '#️⃣', ':symbols:' => '🔣',
            ':arrow_backward:' => '◀️', ':arrow_down:' => '⬇️', ':arrow_forward:' => '▶️', ':arrow_left:' => '⬅️',
            ':capital_abcd:' => '🔠', ':abcd:' => '🔡', ':abc:' => '🔤', ':arrow_lower_left:' => '↙️',
            ':arrow_lower_right:' => '↘️', ':arrow_right:' => '➡️', ':arrow_up:' => '⬆️', ':arrow_upper_left:' => '↖️',
            ':arrow_upper_right:' => '↗️', ':arrow_double_down:' => '⏬', ':arrow_double_up:' => '⏫', ':arrow_down_small:' => '🔽',
            ':arrow_heading_down:' => '⤵️', ':arrow_heading_up:' => '⤴️', ':leftwards_arrow_with_hook:' => '↩️', ':arrow_right_hook:' => '↪️',
            ':left_right_arrow:' => '↔️', ':arrow_up_down:' => '↕️', ':arrow_up_small:' => '🔼', ':arrows_clockwise:' => '🔃',
            ':arrows_counterclockwise:' => '🔄', ':rewind:' => '⏪', ':fast_forward:' => '⏩', ':information_source:' => 'ℹ️',
            ':ok:' => '🆗', ':twisted_rightwards_arrows:' => '🔀', ':repeat:' => '🔁', ':repeat_one:' => '🔂',
            ':new:' => '🆕', ':top:' => '🔝', ':up:' => '🆙', ':cool:' => '🆒',
            ':free:' => '🆓', ':ng:' => '🆖', ':cinema:' => '🎦', ':koko:' => '🈁',
            ':signal_strength:' => '📶', ':u5272:' => '🈹', ':u5408:' => '🈴', ':u55b6:' => '🈺',
            ':u6307:' => '🈯️', ':u6708:' => '🈷️', ':u6709:' => '🈶', ':u6e80:' => '🈵',
            ':u7121:' => '🈚️', ':u7533:' => '🈸', ':u7a7a:' => '🈳', ':u7981:' => '🈲',
            ':sa:' => '🈂️', ':restroom:' => '🚻', ':mens:' => '🚹', ':womens:' => '🚺',
            ':baby_symbol:' => '🚼', ':no_smoking:' => '🚭', ':parking:' => '🅿️', ':wheelchair:' => '♿️',
            ':metro:' => '🚇', ':baggage_claim:' => '🛄', ':accept:' => '🉑', ':wc:' => '🚾',
            ':potable_water:' => '🚰', ':put_litter_in_its_place:' => '🚮', ':secret:' => '㊙️', ':congratulations:' => '㊗️',
            ':m:' => 'Ⓜ️', ':passport_control:' => '🛂', ':left_luggage:' => '🛅', ':customs:' => '🛃',
            ':ideograph_advantage:' => '🉐', ':cl:' => '🆑', ':sos:' => '🆘', ':id:' => '🆔',
            ':no_entry_sign:' => '🚫', ':underage:' => '🔞', ':no_mobile_phones:' => '📵', ':do_not_litter:' => '🚯',
            ':non-potable_water:' => '🚱', ':no_bicycles:' => '🚳', ':no_pedestrians:' => '🚷', ':children_crossing:' => '🚸',
            ':no_entry:' => '⛔️', ':eight_spoked_asterisk:' => '✳️', ':eight_pointed_black_star:' => '✴️', ':heart_decoration:' => '💟',
            ':vs:' => '🆚', ':vibration_mode:' => '📳', ':mobile_phone_off:' => '📴', ':chart:' => '💹',
            ':currency_exchange:' => '💱', ':aries:' => '♈️', ':taurus:' => '♉️', ':gemini:' => '♊️',
            ':cancer:' => '♋️', ':leo:' => '♌️', ':virgo:' => '♍️', ':libra:' => '♎️',
            ':scorpius:' => '♏️', ':sagittarius:' => '♐️', ':capricorn:' => '♑️', ':aquarius:' => '♒️',
            ':pisces:' => '♓️', ':ophiuchus:' => '⛎', ':six_pointed_star:' => '🔯', ':negative_squared_cross_mark:' => '❎',
            ':a:' => '🅰️', ':b:' => '🅱️', ':ab:' => '🆎', ':o2:' => '🅾️',
            ':diamond_shape_with_a_dot_inside:' => '💠', ':recycle:' => '♻️', ':end:' => '🔚', ':on:' => '🔛',
            ':soon:' => '🔜', ':clock1:' => '🕐', ':clock130:' => '🕜', ':clock10:' => '🕙',
            ':clock1030:' => '🕥', ':clock11:' => '🕚', ':clock1130:' => '🕦', ':clock12:' => '🕛',
            ':clock1230:' => '🕧', ':clock2:' => '🕑', ':clock230:' => '🕝', ':clock3:' => '🕒',
            ':clock330:' => '🕞', ':clock4:' => '🕓', ':clock430:' => '🕟', ':clock5:' => '🕔',
            ':clock530:' => '🕠', ':clock6:' => '🕕', ':clock630:' => '🕡', ':clock7:' => '🕖',
            ':clock730:' => '🕢', ':clock8:' => '🕗', ':clock830:' => '🕣', ':clock9:' => '🕘',
            ':clock930:' => '🕤', ':heavy_dollar_sign:' => '💲', ':copyright:' => '©️', ':registered:' => '®️',
            ':tm:' => '™️', ':x:' => '❌', ':heavy_exclamation_mark:' => '❗️', ':bangbang:' => '‼️',
            ':interrobang:' => '⁉️', ':o:' => '⭕️', ':heavy_multiplication_x:' => '✖️', ':heavy_plus_sign:' => '➕',
            ':heavy_minus_sign:' => '➖', ':heavy_division_sign:' => '➗', ':white_flower:' => '💮', ':100:' => '💯',
            ':heavy_check_mark:' => '✔️', ':ballot_box_with_check:' => '☑️', ':radio_button:' => '🔘', ':link:' => '🔗',
            ':curly_loop:' => '➰', ':wavy_dash:' => '〰️', ':part_alternation_mark:' => '〽️', ':trident:' => '🔱',
            ':white_check_mark:' => '✅', ':black_square_button:' => '🔲', ':white_square_button:' => '🔳', ':black_circle:' => '⚫️',
            ':white_circle:' => '⚪️', ':red_circle:' => '🔴', ':large_blue_circle:' => '🔵', ':large_blue_diamond:' => '🔷',
            ':large_orange_diamond:' => '🔶', ':small_blue_diamond:' => '🔹', ':small_orange_diamond:' => '🔸', ':small_red_triangle:' => '🔺',
            ':small_red_triangle_down:' => '🔻', ':black_small_square:' => '▪️', ':black_medium_small_square:' => '◾', ':black_medium_square:' => '◼️',
            ':black_large_square:' => '⬛', ':white_small_square:' => '▫️', ':white_medium_small_square:' => '◽', ':white_medium_square:' => '◻️',
            ':white_large_square:' => '⬜',
        ];

        // Check there is no character before the emoji marker
        if (!preg_match('/^(\s|)$/', $Excerpt['before'])) {
            return null;
        }

        if (preg_match('/^(:)([a-zA-Z0-9_]+)(:)/', $Excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'text' => str_replace(array_keys($emojiMap), $emojiMap, $matches[0]),
                ],
            ];
        }

        return null;
    }

    // Block types
    // -------------------------------------------------------------------------

    protected function parseAttributeData($attributeString)
    {
        if($this->getSetting('special_attributes')) {
            return parent::parseAttributeData($attributeString);
        }

        return [];
    }

    protected function blockFootnote($Line)
    {
        if ($this->getSetting('footnotes')) {
            return parent::blockFootnote($Line);
        }
    }

    protected function blockDefinitionList($Line, $Block)
    {
        if ($this->getSetting('definition_lists')) {
            return parent::blockDefinitionList($Line, $Block);
        }
    }

    protected function blockCode($Line, $Block = null)
    {
        if ($this->getSetting('code') && $this->getSetting('code.blocks')) {
            return parent::blockCode($Line, $Block);
        }
    }

    protected function blockComment($Line)
    {
        if ($this->getSetting('comments')) {
            return parent::blockComment($Line);
        }
    }

    protected function blockList($Line, array $CurrentBlock = null)
    {
        if ($this->getSetting('lists')) {
            return parent::blockList($Line, $CurrentBlock);
        }
    }

    protected function blockQuote($Line)
    {
        if ($this->getSetting('quotes')) {
            return parent::blockQuote($Line);
        }
    }

    protected function blockRule($Line)
    {
        if ($this->getSetting('thematic_breaks')) {
            return parent::blockRule($Line);
        }
    }

    protected function blockMarkup($Line)
    {
        if ($this->getSetting('markup')) {
            return parent::blockMarkup($Line);
        }
    }

    protected function blockReference($Line)
    {
        if ($this->getSetting('references')) {
            return parent::blockReference($Line);
        }
    }

    protected function blockTable($Line, $Block = null)
    {
        if ($this->getSetting('tables')) {
            return parent::blockTable($Line, $Block);
        }
    }

    /**
     * This method is responsible for handling block-level math expressions in the parsed Markdown.
     * It checks if the given line contains a block-level math expression and returns the appropriate block structure.
     *
     * @param array $Line The line to be processed.
     *
     * @return (mixed|string[])[]|null
     *
     * @psalm-return array{element: array{text: ''}, start: mixed, end: mixed}|null
     */
    protected function blockMathNotation($Line)
    {
        if (!$this->getSetting('math') || !$this->getSetting('math.block')) {
            return null;
        }

        foreach ($this->settings['math']['block']['delimiters'] as $config) {

            $leftMarker = preg_quote($config['left'], '/');
            $regex = '/^(?<!\\\\)(' . $leftMarker . ')(?!.)$/';


            if (preg_match($regex, $Line['text'])) {
                return [
                    'element' => [
                        'text' => '',
                    ],
                    'start' => $config['left'], // Store the start marker
                    'end' => $config['right'], // Store the end marker
                ];
            }
        }

        return;
    }

    /**
     * Continues the block math notation parsing.
     *
     * @param  array $Line  The current line being parsed.
     * @param  array $Block The current block being parsed.
     * @return array|null The updated block or null if the block is complete.
     */
    protected function blockMathNotationContinue($Line, $Block)
    {
        if (isset($Block['complete'])) {
            return;
        }

        if (isset($Block['interrupted'])) {
            $Block['element']['text'] .= str_repeat("\n", $Block['interrupted']);
            unset($Block['interrupted']);
        }

        // Double escape the backslashes for regex pattern
        $rightMarker = preg_quote($Block['end'], '/');
        $regex = '/^(?<!\\\\)(' . $rightMarker . ')$/';

        if (preg_match($regex, $Line['text'])) {
            $Block['complete'] = true;
            $Block['math'] = true;
            $Block['element']['text'] = $Block['start'] . $Block['element']['text'] . $Block['end'];
            return $Block;
        }

        $Block['element']['text'] .= "\n" . $Line['body'];

        return $Block;
    }

    /**
     * Returns the complete block of math notation.
     *
     * @param  string $Block The block of math notation.
     * @return string The complete block of math notation.
     */
    protected function blockMathNotationComplete($Block)
    {
        return $Block;
    }


    /**
     * This method handles the parsing of fenced code blocks in the ParsedownExtended class.
     *
     * @param array $Line The current line being parsed.
     *
     * @return array|null The parsed block or null if code blocks are disabled.
     */
    protected function blockFencedCode($Line)
    {
        if (!$this->getSetting('code') or !$this->getSetting('code.blocks')) {
            return;
        }

        $Block = parent::blockFencedCode($Line);
        $marker = $Line['text'][0];
        $openerLength = strspn($Line['text'], $marker);

        // Extract language from the line
        $parts = explode(' ', trim(substr($Line['text'], $openerLength)), 2);
        $language = strtolower($parts[0]);

        // Check if diagrams are enabled
        if (!$this->getSetting('diagrams')) {
            return $Block;
        }

        $extensions = [
            'mermaid' => ['div', 'mermaid'],
            'chart' => ['canvas', 'chartjs'],
            // Add more languages here as needed
        ];

        if (isset($extensions[$language])) {
            [$elementName, $class] = $extensions[$language];

            if(!$this->legacyMode) {
                // 1.8
                return [
                    'char' => $marker,
                    'openerLength' => $openerLength,
                    'element' => [
                        'name' => $elementName,
                        'element' => [
                            'text' => '',
                        ],
                        'attributes' => [
                            'class' => $class,
                        ],
                    ],
                ];
            } else {
                // 1.7
                return [
                    "char" => $marker,
                    'openerLength' => $openerLength,
                    "element" => [
                        "name" => $elementName,
                        "handler" => "element",
                        "text" => [
                            "text" => "",
                        ],
                        "attributes" => [
                            "class" => $class,
                        ],
                    ],
                ];
            }
        }

        return $Block;
    }

    /**
     * This function is used to generate list items and tasked list items.
     * It handles both legacy mode and non-legacy mode.
     *
     * @param array $lines The lines of text to be processed.
     *
     * @return mixed The processed list items.
     */
    protected function li($lines)
    {
        if (!$this->getSetting('lists.tasks')) {
            return parent::li($lines);
        }

        if ($this->legacyMode) {
            $markup = $this->lines($lines);

            // Get first 4 charhacters of the markup
            $firstFourChars = substr($markup, 4, 4);
            // if it is a checkbox
            if (preg_match('/^\[[x ]\]/i', $firstFourChars, $matches)) {
                // check if it is checked
                if (strtolower($matches[0]) === '[x]') {
                    // replace from the 4th character and 4 characters after with a checkbox
                    $markup = substr_replace($markup, '<input type="checkbox" disabled="disabled" checked="checked" />', 4, 4);
                } else {
                    // replace from the 4th character and 4 characters after with a checkbox
                    $markup = substr_replace($markup, '<input type="checkbox" disabled="disabled" />', 4, 4);
                }
            }

            $trimmedMarkup = trim($markup);

            if (! in_array('', $lines) and substr($trimmedMarkup, 0, 3) === '<p>') {
                $markup = $trimmedMarkup;
                $markup = substr($markup, 3);

                $position = strpos($markup, "</p>");

                $markup = substr_replace($markup, '', $position, 4);
            }

            return $markup;
        } else {
            $Elements = $this->linesElements($lines);

            $text = $Elements[0]['handler']['argument'];
            $firstFourChars = substr($text, 0, 4);
            if (preg_match('/^\[[x ]\]/i', $firstFourChars, $matches)) {
                $Elements[0]['handler']['argument'] = substr_replace($text, '', 0, 4);
                if (strtolower($matches[0]) === '[x]') {
                    $Elements[0]['attributes'] = [
                        'checked' => 'checked',
                        'type' => 'checkbox',
                        'disabled' => 'disabled',
                    ];
                } else {
                    $Elements[0]['attributes'] = [
                        'type' => 'checkbox',
                        'disabled' => 'disabled',
                    ];
                }
                $Elements[0]['name'] = 'input';
            }


            if (! in_array('', $lines)
                and isset($Elements[0]) and isset($Elements[0]['name'])
                and $Elements[0]['name'] === 'p'
            ) {
                unset($Elements[0]['name']);
            }

            return $Elements;
        }
    }


    /**
     * Heading process.
     * Creates heading block element and stores to the ToC list. It overrides
     * the parent method: \Parsedown::blockHeader() and returns $Block array if
     * the $Line is a heading element.
     *
     * @param  array $Line Array that Parsedown detected as a block type element.
     * @return void|array   Array of Heading Block.
     */
    protected function blockHeader($Line)
    {
        if (!$this->getSetting('headings')) {
            return;
        }

        $Block = parent::blockHeader($Line);

        if (! empty($Block)) {
            $text = $Block['element']['text'] ?? $Block['element']['handler']['argument'] ?? '';
            $level = $Block['element']['name'];
            $id = $Block['element']['attributes']['id'] ?? $this->createAnchorID($text);

            $Block['element']['attributes'] = ['id' => $id];

            // Check if heading level is in the selectors
            if (!in_array($level, $this->getSetting('toc.headings'))) {
                return $Block;
            }
            
            $this->setContentsList(['text' => $text, 'id' => $id, 'level' => $level]);

            return $Block;
        }
    }

    protected function blockSetextHeader($Line, $Block = null)
    {
        if (!$this->getSetting('headings')) {
            return;
        }

        $Block = parent::blockSetextHeader($Line, $Block);

        if (! empty($Block)) {
            $text = $Block['element']['text'] ?? $Block['element']['handler']['argument'] ?? '';
            $level = $Block['element']['name'];
            $id = $Block['element']['attributes']['id'] ?? $this->createAnchorID($text);

            $Block['element']['attributes'] = ['id' => $id];
            
            // Check if heading level is in the selectors
            if (!in_array($level, $this->getSetting('toc.headings'))) {
                return $Block;
            }
            
            $this->setContentsList(['text' => $text, 'id' => $id, 'level' => $level]);

            return $Block;
        }
    }


    protected function blockAbbreviation($Line)
    {
        if ($this->getSetting('abbreviations')) {
            foreach ($this->getSetting('abbreviations.predefine') as $abbreviations => $description) {
                $this->DefinitionData['Abbreviation'][$abbreviations] = $description;
            }

            if ($this->getSetting('abbreviations.allow_custom_abbr')) {
                return parent::blockAbbreviation($Line);
            }

            return;
        }
    }

    /**
     * (Override)
     * Tablespan
     * Modifyed version of Tablespan by @KENNYSOFT
     */
    protected function blockTableComplete(array $block): array
    {
        if (!$this->getSetting('tables.tablespan')) {
            return $block;
        }

        if ($this->legacyMode === true) {
            // 1.7
            $headerElements = & $block['element']['text'][0]['text'][0]['text'];
        } else {
            // 1.8
            $headerElements = & $block['element']['elements'][0]['elements'][0]['elements'];
        }

        for ($index = count($headerElements) - 1; $index >= 0; --$index) {
            $colspan = 1;
            $headerElement = & $headerElements[$index];

            if ($this->legacyMode === true) {
                // 1.7
                while ($index && $headerElements[$index - 1]['text'] === '>') {
                    $colspan++;
                    $PreviousHeaderElement = & $headerElements[--$index];
                    $PreviousHeaderElement['merged'] = true;
                    if (isset($PreviousHeaderElement['attributes'])) {
                        $headerElement['attributes'] = $PreviousHeaderElement['attributes'];
                    }
                }
            } else {
                // 1.8
                while ($index && '>' === $HeaderElements[$index - 1]['handler']['argument']) {
                    $colspan++;
                    $PreviousHeaderElement = & $headerElements[--$index];
                    $PreviousHeaderElement['merged'] = true;
                    if (isset($PreviousHeaderElement['attributes'])) {
                        $headerElement['attributes'] = $PreviousHeaderElement['attributes'];
                    }
                }
            }

            if ($colspan > 1) {
                if (! isset($headerElement['attributes'])) {
                    $headerElement['attributes'] = [];
                }
                $headerElement['attributes']['colspan'] = $colspan;
            }
        }

        for ($index = count($headerElements) - 1; $index >= 0; --$index) {
            if (isset($headerElements[$index]['merged'])) {
                array_splice($headerElements, $index, 1);
            }
        }

        if ($this->legacyMode === true) {
            // 1.7
            $rows = & $block['element']['text'][1]['text'];
        } else {
            // 1.8
            $rows = & $block['element']['elements'][1]['elements'];
        }

        // Colspan
        foreach ($rows as $rowNo => &$row) {
            if ($this->legacyMode === true) {
                // 1.7
                $elements = & $row['text'];
            } else {
                // 1.8
                $elements = & $row['elements'];
            }

            for ($index = count($elements) - 1; $index >= 0; --$index) {
                $colspan = 1;
                $element = & $elements[$index];

                if ($this->legacyMode === true) {
                    // 1.7
                    while ($index && $elements[$index - 1]['text'] === '>') {
                        $colspan++;
                        $PreviousElement = & $elements[--$index];
                        $PreviousElement['merged'] = true;
                        if (isset($PreviousElement['attributes'])) {
                            $element['attributes'] = $PreviousElement['attributes'];
                        }
                    }
                } else {
                    // 1.8
                    while ($index && '>' === $elements[$index - 1]['handler']['argument']) {
                        ++$colspan;
                        $PreviousElement = &$elements[--$index];
                        $PreviousElement['merged'] = true;
                        if (isset($PreviousElement['attributes'])) {
                            $element['attributes'] = $PreviousElement['attributes'];
                        }
                    }
                }

                if ($colspan > 1) {
                    if (! isset($element['attributes'])) {
                        $element['attributes'] = [];
                    }
                    $element['attributes']['colspan'] = $colspan;
                }
            }
        }

        // Rowspan
        foreach ($rows as $rowNo => &$row) {

            if ($this->legacyMode === true) {
                // 1.7
                $elements = & $row['text'];
            } else {
                // 1.8
                $elements = &$row['elements'];
            }

            foreach ($elements as $index => &$element) {
                $rowspan = 1;

                if (isset($element['merged'])) {
                    continue;
                }

                if ($this->legacyMode === true) {
                    // 1.7
                    while ($rowNo + $rowspan < count($rows) && $index < count($rows[$rowNo + $rowspan]['text']) && $rows[$rowNo + $rowspan]['text'][$index]['text'] === '^' && (@$element['attributes']['colspan'] ?: null) === (@$rows[$rowNo + $rowspan]['text'][$index]['attributes']['colspan'] ?: null)) {
                        $rows[$rowNo + $rowspan]['text'][$index]['merged'] = true;
                        $rowspan++;
                    }
                } else {
                    // 1.8
                    while ($rowNo + $rowspan < count($rows) && $index < count($rows[$rowNo + $rowspan]['elements']) && '>' === $rows[$rowNo + $rowspan]['elements'][$index]['handler']['argument'] && (@$element['attributes']['colspan'] ?: null) === (@$rows[$rowNo + $rowspan]['elements'][$index]['attributes']['colspan'] ?: null)) {
                        $rows[$rowNo + $rowspan]['elements'][$index]['merged'] = true;
                        $rowspan++;
                    }
                }

                if ($rowspan > 1) {
                    if (! isset($element['attributes'])) {
                        $element['attributes'] = [];
                    }
                    $element['attributes']['rowspan'] = $rowspan;
                }
            }
        }

        foreach ($rows as $rowNo => &$row) {

            if ($this->legacyMode === true) {
                // 1.7
                $elements = & $row['text'];
            } else {
                // 1.8
                $elements = & $row['elements'];
            }

            for ($index = count($elements) - 1; $index >= 0; --$index) {
                if (isset($elements[$index]['merged'])) {
                    array_splice($elements, $index, 1);
                }
            }
        }

        return $block;
    }



    // Functions related to Table of Contents
    // A slightly modified version of ToC by @KEINOS
    // -------------------------------------------------------------------------

    /**
     * Parses the given markdown string to an HTML string but it leaves the ToC
     * tag as is. It's an alias of the parent method "\parent::text()".
     *
     * @param  string $text Markdown string to be parsed.
     * @return string        Parsed HTML string.
     */
    public function body(string $text): string
    {
        $text = $this->encodeTag($text); // Escapes ToC tag temporarily
        $html = parent::text($text);           // Parses the markdown text
        return $this->decodeTag($html); // Unescapes the ToC tag
    }

    /**
     * Returns the parsed ToC.
     * If the arg is "string" then it returns the ToC in HTML string.
     *
     * @param  string $type_return Type of the return format. "string" or "json".
     * @return string               HTML/JSON string of ToC.
     */
    public function contentsList($type_return = 'string'): string
    {
        switch (strtolower($type_return)) {
        case 'string':
            return $this->contentsListString ? $this->body($this->contentsListString) : '';
        case 'json':
            return json_encode($this->contentsListArray);
        default:
            $backtrace = debug_backtrace();
            $caller = $backtrace[0];
            $errorMessage = "Unknown return type '{$type_return}' given while parsing ToC. Called in " . $caller['file'] . " on line " . $caller['line'];
            throw new InvalidArgumentException($errorMessage);
        }
    }


    /**
     * Allows users to define their own logic for createAnchorID.
     */
    public function setCreateAnchorIDCallback(callable $callback): void
    {
        $this->createAnchorIDCallback = $callback;
    }

    /**
     * Creates an anchor ID for the given text.
     *
     * If a callback is provided, it uses the user-defined logic to create the anchor ID.
     * Otherwise, it uses the default logic which involves normalizing the string, replacing characters, and sanitizing the anchor.
     *
     * @param  string $text The text for which to create the anchor ID.
     * @return string The created anchor ID.
     */
    protected function createAnchorID(string $text): ?string
    {
        // Check settings
        if (!$this->getSetting('headings.auto_anchors')) {
            return null;
        }

        // Use user-defined logic if a callback is provided
        if (is_callable($this->createAnchorIDCallback)) {
            return call_user_func($this->createAnchorIDCallback, $text, $this->getSettings());
        }

        // Default logic

        if ($this->getSetting('headings.auto_anchors.lowercase')) {
            $text = mb_strtolower($text);
        }

        if($this->getSetting('headings.auto_anchors.replacements')) {
            $text = preg_replace(array_keys($this->getSetting('headings.auto_anchors.replacements')), $this->getSetting('headings.auto_anchors.replacements'), $text);
        }

        $text = $this->normalizeString($text);

        if ($this->getSetting('headings.auto_anchors.transliterate')) {
            $text = $this->transliterate($text);
        }

        $text = $this->sanitizeAnchor($text);

        return $this->uniquifyAnchorID($text);
    }

    /**
     * Normalize a string by converting it to lowercase and encoding it to UTF-8.
     *
     * @param  string $text The string to be normalized.
     * @return string The normalized string.
     */
    protected function normalizeString(string $text): string
    {
        return mb_convert_encoding($text, 'UTF-8', mb_list_encodings());
    }

    /**
     * Replaces special characters in a string with their corresponding ASCII equivalents.
     *
     * @param  string $text The input string.
     * @return string The modified string with replaced characters.
     */
    protected function transliterate(string $text): string
    {
        $characterMap = [
            // Latin
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'AA', 'Æ' => 'AE', 'Ç' => 'C',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
            'Ø' => 'OE', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
            'ß' => 'ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'aa', 'æ' => 'ae', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
            'ø' => 'oe', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
            'ÿ' => 'y',

            // Latin symbols
            '©' => '(c)', '®' => '(r)', '™' => '(tm)',

            // Greek
            'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => 'TH',
            'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => 'X', 'Ο' => 'O', 'Π' => 'P',
            'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'O',
            'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'O', 'Ϊ' => 'I',
            'Ϋ' => 'Y',
            'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => 'th',
            'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => 'x', 'ο' => 'o', 'π' => 'p',
            'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'o',
            'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'o', 'ς' => 's',
            'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',

            // Turkish
            'Ş' => 'S', 'İ' => 'I', 'Ğ' => 'G',
            'ş' => 's', 'ı' => 'i', 'ğ' => 'g',

            // Russian
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
            'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
            'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch', 'Ъ' => 'U', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
            'Я' => 'Ya',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => 'u', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
            'я' => 'ya',

            // Ukrainian
            'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
            'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',

            // Czech
            'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
            'Ž' => 'Z',
            'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
            'ž' => 'z',

            // Polish
            'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'E', 'Ł' => 'L', 'Ń' => 'N', 'Ś' => 'S', 'Ź' => 'Z',
            'Ż' => 'Z',
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ś' => 's', 'ź' => 'z',
            'ż' => 'z',

            // Latvian
            'Ā' => 'A', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'I', 'Ķ' => 'K', 'Ļ' => 'L', 'Ņ' => 'N', 'Ū' => 'U',
            'ā' => 'a', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n', 'ū' => 'u',
        ];

        return strtr($text, $characterMap);
    }

    /**
     * Sanitizes an anchor text by removing special characters, replacing spaces with dashes,
     * and removing consecutive dashes.
     *
     * @param  string $text The anchor text to sanitize.
     * @return string The sanitized anchor text.
     */
    protected function sanitizeAnchor(string $text): string
    {
        $delimiter = $this->getSetting('headings.auto_anchors.delimiter');
        // Replace non-alphanumeric characters with our delimiter
        $text = preg_replace('/[^\p{L}\p{Nd}]+/u', $delimiter, $text);
        // Remove consecutive delimiters
        $text = preg_replace('/(' . preg_quote($delimiter, '/') . '){2,}/', '$1', $text);
        // Remove leading and trailing delimiters
        $text = trim($text, $delimiter);
        return $text;
    }

    /**
     * Generate a unique anchor ID based on the given text.
     *
     * @param  string $text The text to generate the anchor ID from.
     * @return string The unique anchor ID.
     */
    protected function uniquifyAnchorID(string $text): string
    {
        $blacklist = $this->getSetting('headings.auto_anchors.blacklist');

        // Initialize the count for this text if not already set
        if (!isset($this->anchorDuplicates[$text])) {
            $this->anchorDuplicates[$text] = 0;
        }

        // Check if the given text is not in the blacklist and does not have any duplicates
        if (!in_array($text, $blacklist) && !isset($this->anchorDuplicates[$text])) {
            return $text;
        }

        $originalText = $text;
        $count = $this->anchorDuplicates[$originalText] ?? 0;

        // Generate a unique anchor ID by appending a count to the original text
        do {
            $count++;
            $text = $originalText . '-' . $count;
        } while (in_array($text, $blacklist) || isset($this->anchorDuplicates[$text]));

        // Store the count for the original text in the duplicates array
        $this->anchorDuplicates[$originalText] = $count;
        return $text;
    }

    /**
     * Decodes the hashed ToC tag to an original tag and replaces.
     *
     * @param  string $text
     * @return string
     */
    protected function decodeTag(string $text): string
    {
        $salt = $this->getSalt();
        $tag_origin = $this->getTagToc();
        $tag_hashed = hash('sha256', $salt . $tag_origin);

        if (strpos($text, $tag_hashed) === false) {
            return $text;
        }

        return str_replace($tag_hashed, $tag_origin, $text);
    }

    /**
     * Encodes the ToC tag to a hashed tag and replace.
     *
     * @param  string $text
     * @return string
     */
    protected function encodeTag(string $text): string
    {
        $salt = $this->getSalt();
        $tag_origin = $this->getTagToc();

        if (strpos($text, $tag_origin) === false) {
            return $text;
        }

        $tag_hashed = hash('sha256', $salt . $tag_origin);

        return str_replace($tag_origin, $tag_hashed, $text);
    }

    /**
     * Get only the text from a markdown string.
     * It parses to HTML once then trims the tags to get the text.
     *
     * @param  string $text Markdown text.
     * @return string
     */
    protected function fetchText($text): string
    {
        return trim(strip_tags($this->line($text)));
    }

    /**
     * Gets the ID attribute of the ToC for HTML tags.
     *
     * @return string
     */
    protected function getIdAttributeToc(): string
    {
        if (isset($this->id_toc) && ! empty($this->id_toc)) {
            return $this->id_toc;
        }

        return self::TOC_ID_ATTRIBUTE_DEFAULT;
    }

    /**
     * Unique string to use as a salt value.
     *
     * @return string
     */
    protected function getSalt(): string
    {
        static $salt;
        if (isset($salt)) {
            return $salt;
        }

        $salt = hash('md5', time());
        return $salt;
    }

    /**
     * Gets the markdown tag for ToC.
     *
     * @return string
     */
    protected function getTagToc(): string
    {
        if (isset($this->tag_toc) && !empty($this->tag_toc)) {
            return $this->tag_toc;
        }

        return self::TOC_TAG_DEFAULT;
    }

    /**
     * Set/stores the heading block to ToC list in a string and array format.
     *
     * @param  array $Content Heading info such as "level","id" and "text".
     * @return void
     */
    protected function setContentsList(array $Content): void
    {
        // Stores as an array
        $this->setContentsListAsArray($Content);
        // Stores as string in markdown list format.
        $this->setContentsListAsString($Content);
    }

    /**
     * Sets/stores the heading block info as an array.
     *
     * @param  array $Content
     * @return void
     */
    protected function setContentsListAsArray(array $Content): void
    {
        $this->contentsListArray[] = $Content;
    }

    /**
     * Sets/stores the heading block info as a list in markdown format.
     *
     * @param  array $Content Heading info such as "level","id" and "text".
     * @return void
     */
    protected function setContentsListAsString(array $Content): void
    {
        $text = $this->fetchText($Content['text']);
        $id = $Content['id'];
        $level = (int) trim($Content['level'], 'h');
        $link = "[{$text}](#{$id})";

        if ($this->firstHeadLevel === 0) {
            $this->firstHeadLevel = $level;
        }
        $indentLevel = max(1, $level - ($this->firstHeadLevel - 1));
        $indent = str_repeat('  ', $indentLevel);

        $this->contentsListString .= "{$indent}- {$link}" . PHP_EOL;
    }

    /**
     * Sets the user defined ToC markdown tag.
     *
     * @param  string $tag
     * @return void
     */
    public function setTagToc($tag): void
    {
        $tag = trim($tag);
        if (self::escape($tag) === $tag) {
            // Set ToC tag if it's safe
            $this->tag_toc = $tag;
        } else {
            $backtrace = debug_backtrace();
            $caller = $backtrace[0];
            $errorMessage = "Malformed ToC user tag given: {$tag}. Called in " . $caller['file'] . " on line " . $caller['line'];
            throw new InvalidArgumentException($errorMessage);
        }
    }

    /**
     * Parses markdown string to HTML and also the "[toc]" tag as well.
     * It overrides the parent method: \Parsedown::text().
     *
     * @param  string $text
     * @return string
     */
    public function text($text): string
    {
        $html = $this->body($text);
        $tag_origin = $this->getTagToc();
        if (strpos($text, $tag_origin) === false) {
            return $html;
        }

        $toc_data = $this->contentsList();
        $toc_id = $this->getIdAttributeToc();
        return str_replace("<p>{$tag_origin}</p>", "<div id=\"{$toc_id}\">{$toc_data}</div>", $html);
    }


    // Settings
    // -------------------------------------------------------------------------

    /**
     * Sets a setting value in the ParsedownExtended class.
     *
     * @param string $settingName  The name of the setting to be set.
     * @param mixed  $settingValue The value to be set for the setting.
     *
     * @return static
     */
    public function setSetting(string $settingName, $settingValue, bool $overwrite = false): self
    {
        // Split the settingName into parts using dot as separator
        $settingParts = explode('.', $settingName);
    
        // Reference to the settings array
        $currentSettings = &$this->settings;
    
        // Iterate through the parts of the setting name
        foreach ($settingParts as $part) {
            // Check if the part exists in the current settings
            if (!isset($currentSettings[$part])) {
                // The setting name is invalid, return an error message
                throw new \InvalidArgumentException("Invalid setting name: $settingName");
            }
            // Move to the next level of settings
            $currentSettings = &$currentSettings[$part];
        }
    
        /**
         * If the setting value is an array and the 'enabled' key is not set in the setting value,
         * but it is set in the current settings, then set the 'enabled' key in the setting value
         * to the value of the 'enabled' key in the current settings.
         */
        if (is_array($settingValue) && isset($currentSettings['enabled']) && !isset($settingValue['enabled'])) {
            $settingValue['enabled'] = $currentSettings['enabled'];
        }
    
        /**
         * If $overwrite is false and both current and new setting values are arrays,
         * merge them. Otherwise, replace the current setting with the new value.
         */
        if (!$overwrite && is_array($currentSettings) && is_array($settingValue)) {
            // Merge the arrays, preserving existing elements and adding new ones from $settingValue
            $currentSettings = array_merge($currentSettings, $settingValue);
        } else {
            // If not merging, then handle setting the value based on its type or replacing outright
            if (is_bool($settingValue) && isset($currentSettings['enabled'])) {
                $currentSettings['enabled'] = $settingValue;
            } else {
                // Update the setting value, potentially replacing it entirely
                $currentSettings = $settingValue;
            }
        }
    
        // Return $this to allow chaining
        return $this;
    }


    /**
     * Sets multiple setting values for the ParsedownExtended class.
     *
     * @param array $settings An associative array where keys are setting names and values are the new values.
     *
     * @return static
     *
     * @throws InvalidArgumentException If any of the setting keys do not exist or if the values are of invalid types.
     */
    public function setSettings(array $settings): self
    {
        foreach ($settings as $key => $value) {
            // Use the existing setSetting method to set each individual setting
            $this->setSetting($key, $value);
        }

        return $this;
    }


    /**
     * Retrieves the value of a specific setting based on the provided key.
     *
     * @param  string $key The key of the setting to retrieve.
     * @return mixed The value of the setting.
     * @throws InvalidArgumentException If the setting does not exist.
     */
    public function getSetting(string $key)
    {
        $keys = explode('.', $key);
        $current = $this->settings;

        foreach ($keys as $part) {
            if (isset($current[$part])) {
                $current = $current[$part];
            } else {
                $backtrace = debug_backtrace();
                $caller = $backtrace[0]; // Gets the immediate caller. Adjust the index for more depth.

                $errorMessage = "Setting '$key' does not exist. Called in " . ($caller['file'] ?? 'unknown') . " on line " . ($caller['line'] ?? 'unknown');
                throw new InvalidArgumentException($errorMessage);
            }
        }

        // For a parent setting with child properties, return the 'enabled' status.
        // This assumes that parent settings are always arrays with an 'enabled' property.
        if (is_array($current) && isset($current['enabled'])) {
            return $current['enabled'];
        }

        // For child settings or simple boolean settings, return their direct value.
        return $current;
    }

    /**
     * Retrieves all settings for the ParsedownExtended class.
     *
     * @return array Returns an array of all settings.
     */
    public function getSettings(): array
    {
        return $this->settings;
    }


    // Helper functions
    // -------------------------------------------------------------------------


    /**
     * @return never
     */
    private function throwSettingException(string $key, string $part)
    {
        $backtrace = debug_backtrace();
        $caller = $backtrace[0];
        $errorMessage = "Setting '$part' in path '$key' does not exist. Called in " . ($caller['file'] ?? 'unknown') . " on line " . ($caller['line'] ?? 'unknown');
        throw new InvalidArgumentException($errorMessage);
    }

    /**
     * @return never
     */
    private function throwInvalidTypeException(string $key)
    {
        $backtrace = debug_backtrace();
        $caller = $backtrace[0];
        $errorMessage = "Invalid type for setting '$key'. Expected array or boolean. Called in " . ($caller['file'] ?? 'unknown') . " on line " . ($caller['line'] ?? 'unknown');
        throw new InvalidArgumentException($errorMessage);
    }

    /**
     * Adds an inline type to the ParsedownExtended class.
     *
     * @param string|string[] $markers  The marker(s) for the inline type.
     * @param string          $funcName The name of the function to handle the inline type.
     *
     * @return void
     *
     * @psalm-param list{0: '('|'<'|'\', 1: '$'|'.'|'>', 2?: '+'|'-', 3?: '!'|'.', 4?: '''|'?', 5?: '"', 6?: '`'}|string $markers
     */
    private function addInlineType($markers, string $funcName): void
    {
        // Ensure $markers is an array, even if it's a single marker
        $markers = (array) $markers;

        foreach ($markers as $marker) {
            if (!isset($this->InlineTypes[$marker])) {
                $this->InlineTypes[$marker] = [];
            }

            // add to the beginning of the array so it has priority
            // array_unshift($this->InlineTypes[$marker], $funcName);
            $this->InlineTypes[$marker][] = $funcName;
            $this->inlineMarkerList .= $marker;
        }
    }


    /**
     * Adds a block type to the ParsedownExtended class.
     *
     * @param string[] $markers  The marker(s) representing the block type.
     * @param string   $funcName The name of the function to handle the block type.
     *
     * @return void
     *
     * @psalm-param list{'\', '$'} $markers
     */
    private function addBlockType(array $markers, string $funcName): void
    {
        // Ensure $markers is an array, even if it's a single marker
        $markers = (array) $markers;

        foreach ($markers as $marker) {
            if (!isset($this->BlockTypes[$marker])) {
                $this->BlockTypes[$marker] = [];
            }
            // add to the beginning of the array so it has priority
            //array_unshift($this->BlockTypes[$marker], $funcName);
            $this->BlockTypes[$marker][] = $funcName;
        }
    }

    /**
     * Overwrite to allow for custom block types
     */
    protected function element(array $Element)
    {
        if ($this->legacyMode) {
            // Check if the name is empty
            if (empty($Element['name'])) {
                return $Element['text'] ?? '';
            }
        }

        // Use the parent
        return parent::element($Element);
    }


    /**
     * Overwrite line from Parsedown to allow for more precise control over inline elements
     * line() is 1.7 version of lineElements() from 1.8, so we overwrite it too, it will not be called
     * when using 1.8 version of parsedown
     *
     * @return string
     */
    public function line($text, $nonNestables = [])
    {
        $markup = '';

        // $Excerpt is based on the first occurrence of a marker

        while ($Excerpt = strpbrk($text, $this->inlineMarkerList)) {
            $marker = $Excerpt[0];

            $markerPosition = strpos($text, $marker);

            // Get the charecter before the marker
            $before = $markerPosition > 0 ? $text[$markerPosition - 1] : '';

            $Excerpt = [
                'text' => $Excerpt,
                'context' => $text,
                'before' => $before,
                'parent' => $this,
                // 'inlineTypes' => isset($this->InlineTypes[$marker]) ? $this->InlineTypes[$marker] : [] // Not apresent in original Parsedown
            ];

            foreach ($this->InlineTypes[$marker] as $inlineType) {
                // check to see if the current inline type is nestable in the current context

                if (! empty($nonNestables) and in_array($inlineType, $nonNestables)) {
                    continue;
                }

                $Inline = $this->{'inline'.$inlineType}($Excerpt);

                if (! isset($Inline)) {
                    continue;
                }


                // makes sure that the inline belongs to "our" marker

                if (isset($Inline['position']) and $Inline['position'] > $markerPosition) {
                    continue;
                }

                // sets a default inline position

                if (! isset($Inline['position'])) {
                    $Inline['position'] = $markerPosition;
                }

                // cause the new element to 'inherit' our non nestables

                foreach ($nonNestables as $non_nestable) {
                    $Inline['element']['nonNestables'][] = $non_nestable;
                }

                // the text that comes before the inline
                $unmarkedText = substr($text, 0, $Inline['position']);

                // compile the unmarked text
                $markup .= $this->unmarkedText($unmarkedText);

                // compile the inline
                $markup .= $Inline['markup'] ?? $this->element($Inline['element']);

                // remove the examined text
                $text = substr($text, $Inline['position'] + $Inline['extent']);

                continue 2;
            }

            // the marker does not belong to an inline

            $unmarkedText = substr($text, 0, $markerPosition + 1);

            $markup .= $this->unmarkedText($unmarkedText);

            $text = substr($text, $markerPosition + 1);
        }

        $markup .= $this->unmarkedText($text);

        return $markup;
    }

    /**
     * Overwrite lineElements from Parsedown to allow for more precise control over inline elements
     * lineElements() is 1.8 version of line() from 1.7, so we overwrite it too, it will not be called
     * when using 1.7 version of parsedown
     *
     * @psalm-return list{mixed,...}
     */
    protected function lineElements($text, $nonNestables = []): array
    {
        $Elements = [];

        $nonNestables = (
            empty($nonNestables)
            ? []
            : array_combine($nonNestables, $nonNestables)
        );

        // $Excerpt is based on the first occurrence of a marker

        while ($Excerpt = strpbrk($text, $this->inlineMarkerList)) {
            $marker = $Excerpt[0];

            $markerPosition = strlen($text) - strlen($Excerpt);

            // Get the charecter before the marker
            $before = $markerPosition > 0 ? $text[$markerPosition - 1] : '';

            $Excerpt = ['text' => $Excerpt, 'context' => $text, 'before' => $before];

            foreach ($this->InlineTypes[$marker] as $inlineType) {
                // check to see if the current inline type is nestable in the current context

                if (isset($nonNestables[$inlineType])) {
                    continue;
                }

                $Inline = $this->{"inline$inlineType"}($Excerpt);

                if (! isset($Inline)) {
                    continue;
                }

                // makes sure that the inline belongs to "our" marker

                if (isset($Inline['position']) and $Inline['position'] > $markerPosition) {
                    continue;
                }

                // sets a default inline position

                if (! isset($Inline['position'])) {
                    $Inline['position'] = $markerPosition;
                }

                // cause the new element to 'inherit' our non nestables


                $Inline['element']['nonNestables'] = isset($Inline['element']['nonNestables'])
                    ? array_merge($Inline['element']['nonNestables'], $nonNestables)
                    : $nonNestables
                ;

                // the text that comes before the inline
                $unmarkedText = substr($text, 0, $Inline['position']);

                // compile the unmarked text
                $InlineText = $this->inlineText($unmarkedText);
                $Elements[] = $InlineText['element'];

                // compile the inline
                $Elements[] = $this->extractElement($Inline);

                // remove the examined text
                $text = substr($text, $Inline['position'] + $Inline['extent']);

                continue 2;
            }

            // the marker does not belong to an inline

            $unmarkedText = substr($text, 0, $markerPosition + 1);

            $InlineText = $this->inlineText($unmarkedText);
            $Elements[] = $InlineText['element'];

            $text = substr($text, $markerPosition + 1);
        }

        $InlineText = $this->inlineText($text);
        $Elements[] = $InlineText['element'];

        foreach ($Elements as &$Element) {
            if (! isset($Element['autobreak'])) {
                $Element['autobreak'] = false;
            }
        }

        return $Elements;
    }
}
