<?php

use PhpCsFixer\AbstractAlignFixerHelper;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\Operator\AlignDoubleArrowFixerHelper;
use PhpCsFixer\Fixer\Operator\AlignEqualsFixerHelper;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

final class BinaryOperatorAlignmentFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * @var AbstractAlignFixerHelper[]
     */
    private $alignFixerHelpers = [];

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'CsFixer/binary_operator_alignment';
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return -9999;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Binary operators should be surrounded by at least one space.',
            [
                new CodeSample(
                    '<?php

$a   = 9000;
$abc = 90001;

$foo = array(
    "a"   => 9000,
    "abc" => 9001,
);
'
                ),
                new CodeSample(
                    '<?php

$a   = 9000;
$abc = 90001;
',
                    ['align_equals' => false]
                ),
                new CodeSample(
                    '<?php

$a = 9000;
$abc = 90001;
',
                    ['align_equals' => true]
                ),
                new CodeSample(
                    '<?php

$foo = array(
    "a"   => 9000,
    "abc" => 9001,
);
',
                    ['align_double_arrow' => false]
                ),
                new CodeSample(
                    '<?php

$foo = array(
    "a" => 9000,
    "abc" => 9001,
);
',
                    ['align_double_arrow' => true]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        // last and first tokens cannot be an operator
        for ($index = $tokens->count() - 2; $index >= 0; --$index) {
            if (!$tokensAnalyzer->isBinaryOperator($index)) {
                continue;
            }

            $isDeclare = $this->isDeclareStatement($tokens, $index);
            if (false !== $isDeclare) {
                $index = $isDeclare; // skip `declare(foo ==bar)`, see `declare_equal_normalize`
            } else {
                $this->fixWhiteSpaceAroundOperator($tokens, $index);
            }

            // previous of binary operator is now never an operator / previous of declare statement cannot be an operator
            --$index;
        }

        $this->runHelperFixers($file, $tokens);
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        $alignDoubleArrows = new FixerOptionBuilder('align_double_arrow', 'Whether to apply, remove or ignore double arrows alignment.');
        $alignDoubleArrows
            ->setDefault(false)
            ->setAllowedValues([true, false, null])
        ;

        $alignEquals = new FixerOptionBuilder('align_equals', 'Whether to apply, remove or ignore equals alignment.');
        $alignEquals
            ->setDefault(false)
            ->setAllowedValues([true, false, null])
        ;

        return new FixerConfigurationResolver([
            $alignDoubleArrows->getOption(),
            $alignEquals->getOption(),
        ]);
    }

    /**
     * @param Tokens $tokens
     * @param int    $index
     */
    private function fixWhiteSpaceAroundOperator(Tokens $tokens, $index)
    {
        if ($tokens[$index]->isGivenKind(T_DOUBLE_ARROW)) {
            if (true === $this->configuration['align_double_arrow']) {
                if (!isset($this->alignFixerHelpers['align_double_arrow'])) {
                    $this->alignFixerHelpers['align_double_arrow'] = new AlignDoubleArrowFixerHelper();
                }

                return;
            } elseif (null === $this->configuration['align_double_arrow']) {
                return; // configured not to touch the whitespace around the operator
            }
        } elseif ($tokens[$index]->equals('=')) {
            if (true === $this->configuration['align_equals']) {
                if (!isset($this->alignFixerHelpers['align_equals'])) {
                    $this->alignFixerHelpers['align_equals'] = new AlignEqualsFixerHelper();
                }

                return;
            } elseif (null === $this->configuration['align_equals']) {
                return; // configured not to touch the whitespace around the operator
            }
        }

        // fix white space after operator
        if ($tokens[$index + 1]->isWhitespace()) {
            $content = $tokens[$index + 1]->getContent();
            if (' ' !== $content && false === strpos($content, "\n") && !$tokens[$tokens->getNextNonWhitespace($index + 1)]->isComment()) {
                $tokens[$index + 1]->setContent(' ');
            }
        } else {
            $tokens->insertAt($index + 1, new Token([T_WHITESPACE, ' ']));
        }

        // fix white space before operator
        if ($tokens[$index - 1]->isWhitespace()) {
            $content = $tokens[$index - 1]->getContent();
            if (' ' !== $content && false === strpos($content, "\n") && !$tokens[$tokens->getPrevNonWhitespace($index - 1)]->isComment()) {
                $tokens[$index - 1]->setContent(' ');
            }
        } else {
            $tokens->insertAt($index, new Token([T_WHITESPACE, ' ']));
        }
    }

    /**
     * @param Tokens $tokens
     * @param int    $index
     *
     * @return false|int
     */
    private function isDeclareStatement(Tokens $tokens, $index)
    {
        $prevMeaningfulIndex = $tokens->getPrevMeaningfulToken($index);
        if ($tokens[$prevMeaningfulIndex]->isGivenKind(T_STRING)) {
            $prevMeaningfulIndex = $tokens->getPrevMeaningfulToken($prevMeaningfulIndex);
            if ($tokens[$prevMeaningfulIndex]->equals('(')) {
                $prevMeaningfulIndex = $tokens->getPrevMeaningfulToken($prevMeaningfulIndex);
                if ($tokens[$prevMeaningfulIndex]->isGivenKind(T_DECLARE)) {
                    return $prevMeaningfulIndex;
                }
            }
        }

        return false;
    }

    /**
     * @param \SplFileInfo $file
     * @param Tokens       $tokens
     */
    private function runHelperFixers(\SplFileInfo $file, Tokens $tokens)
    {
        /** @var AbstractAlignFixerHelper $helper */
        foreach ($this->alignFixerHelpers as $helper) {
            if ($tokens->isChanged()) {
                $tokens->clearEmptyTokens();
            }

            $helper->fix($tokens);
        }
    }
}


$finder = PhpCsFixer\Finder::create()
    ->exclude([
        'bin',
        'vendor',
        'storage',
        'public',
        'resources',
        'node_modules',
        'laradock',
    ])
    ->in(__DIR__ . '/bootstrap')
    ->in(__DIR__ . '/tests')
    ->in(__DIR__ . '/app')
    ->in(__DIR__ . '/database')
    ->in(__DIR__ . '/config');

return PhpCsFixer\Config::create()
    ->registerCustomFixers([
        new BinaryOperatorAlignmentFixer()
    ])
    ->setRules([
        'psr0'                                 => false,
        'psr4'                                 => false,
        '@Symfony'                             => true,
        '@PSR2'                                => true,
        'ordered_imports'                      => true,
        'phpdoc_order'                         => true,
        'array_syntax'                         => ['syntax' => 'short'],
        'declare_equal_normalize'              => ['space'  => 'single'],
        'phpdoc_add_missing_param_annotation'  => true,
        'concat_space'                         => ['spacing' => 'one'],
        'CsFixer/binary_operator_alignment' => [
            'align_double_arrow' => true,
            'align_equals'       => true,
        ],
    ])
    ->setFinder($finder);
