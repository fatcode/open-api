<?php declare(strict_types=1);

namespace Igni\OpenApi\Annotation\Parser;

use Igni\OpenApi\Annotation\Parser\MetaData\Annotation;
use Igni\OpenApi\Annotation\Parser\MetaData\Enum;
use Igni\OpenApi\Annotation\Parser\MetaData\Required;
use Igni\OpenApi\Annotation\Parser\MetaData\Target;
use Igni\OpenApi\Exception\ParserException;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class Parser
{
    private const S_ANNOTATION = 1;
    private const S_ANNOTATION_ARGUMENTS = 2;
    private const S_ARRAY = 5;

    private $ignoreNotImported = false;
    private $fileImports;
    private $phpParser;

    private $ignored = [

    ];

    private $namespaces = [

    ];

    private $annotations = [
        'Annotation' => Annotation::class,
        'Enum' => Enum::class,
        'Required' => Required::class,
        'Target' => Target::class,
    ];

    private $metaData = [
        Annotation::class => [
            'target' => [Target::TARGET_CLASS],
            'constructor' => false,
            'validate' => false,
            'properties' => [],
        ],
        Required::class => [
            'target' => [Target::TARGET_PROPERTY],
            'constructor' => false,
            'validate' => false,
            'properties' => [],
        ],
        Target::class => [
            'constructor' => true,
            'validate' => false,
            'properties' => [],
        ],
        Enum::class => [
            'constructor' => true,
            'validate' => false,
            'properties' => [],
        ]
    ];

    public function __construct()
    {
        $this->phpParser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
    }

    public function addIgnore(string $name) : void
    {
        $this->ignored[] = $name;
    }

    public function ignoreNotImportedAnnotations(bool $ignore = true) : void
    {
        $this->ignoreNotImported = $ignore;
    }

    /**
     * @param DocBlock $docBlock
     * @return array
     */
    public function parse(DocBlock $docBlock): array
    {
        $tokenizer = new Tokenizer((string) $docBlock);
        $tokenizer->tokenize();

        // Lets search for fist annotation occurrence in docblock
        if (!$tokenizer->seek(Token::T_AT)) {
            // No annotations in docblock.
            return [];
        }

        $state = null;

        $propertiesStack = [];
        $annotationsStack = [];

        $annotations = [];
        while ($tokenizer->valid()) {
            $token = $tokenizer->current();
            switch ($token->getType()) {
                case Token::T_AT:
                    $tokenizer->next();
                    $state = self::S_ANNOTATION;
                    $annotationsStack[] = $this->catchAnnotationName($tokenizer, $docBlock);
                    $propertiesStack[] = [];
                    break;
                case Token::T_OPEN_PARENTHESIS:
                    break;
                case Token::T_CLOSE_PARENTHESIS:
                    break;
                case Token::T_OPEN_BRACKET:
                    break;
                case Token::T_CLOSE_BRACKET:
                    break;
                case Token::T_COMMA:
                    break;
            }
        }

        if ($state === self::S_ANNOTATION) {
            $annotations = $this->instantiateAnnotation($annotationsStack, $propertiesStack);
        }

        return $annotations;
    }

    private function instantiateAnnotation(array &$annotationStack, array &$propertiesStack)
    {
        $annotation = array_pop($annotationStack);
        $properties = array_pop($propertiesStack);


    }

    private function catchAnnotationName(Tokenizer $tokenizer, DocBlock $context) : string
    {
        $name = '';
        while ($tokenizer->valid()) {
            $token = $tokenizer->current();
            switch ($token->getType()) {
                case Token::T_IDENTIFIER:
                case Token::T_NAMESPACE_SEPARATOR:
                    $name .= $token->getValue();
                    break;

                case Token::T_OPEN_PARENTHESIS:
                case Token::T_ASTERISK:
                case Token::T_AT:
                    return $name;

                default:
                    throw ParserException::forUnexpectedToken($token, $context);
                    break;
            }
            $tokenizer->next();
        }

        return $name;
    }

    private function gatherAnnotationMetaData(string $annotation) : void
    {

    }

    private function getFileImports(DocBlock $context) : array
    {
        $filename = $context->getFilename();
        if (isset($this->fileImports[$filename])) {
            return $this->fileImports[$filename];
        }

        if (empty($filename) || !is_file($filename) || !is_readable($filename)) {
            return $this->fileImports[$filename] = [];
        }

        $useStatements = [];
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class($useStatements) extends NodeVisitorAbstract {
            private $useStatements;

            public function __construct(&$useStatements)
            {
                $this->useStatements = &$useStatements;
            }

            public function enterNode(Node $node) {
                if ($node instanceof Node\Stmt\UseUse) {
                    $this->useStatements[strtolower((string) $node->getAlias())] = (string) $node->name;
                }
            }
        });

        try {
            $ast = $this->phpParser->parse(file_get_contents($filename));
            $traverser->traverse($ast);
        } catch (Error $exception) {
            return $this->fileImports[$filename] = [];
        }

        return $this->fileImports[$filename] = $useStatements;
    }
}