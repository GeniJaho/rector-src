<?php

declare(strict_types=1);

namespace Rector\Removing\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Webmozart\Assert\Assert;

/**
 * @see \Rector\Tests\Removing\Rector\FuncCall\RemoveFuncCallRector\RemoveFuncCallRectorTest
 */
final class RemoveFuncCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var string[]
     */
    private array $removedFunctions = [];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove function', [
            new ConfiguredCodeSample(
                <<<'CODE_SAMPLE'
$x = 'something';
var_dump($x);
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
$x = 'something';
CODE_SAMPLE
                ,
                ['var_dump']
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class, Node\Stmt\Expression::class];
    }

    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Node\Stmt\Expression) {
            $this->removeExpressionNodeIfNeeded($node);

            return null;
        }

        $this->removeFunctionCallNodeIfNeeded($node);

        return null;
    }

    private function removeExpressionNodeIfNeeded(Node\Stmt\Expression $node): void
    {
        if (! $node->expr instanceof FuncCall) {
            return;
        }

        foreach ($this->removedFunctions as $removedFunction) {
            if (!$this->isName($node->expr->name, $removedFunction)) {
                continue;
            }

            $this->removeNode($node);

            break;
        }
    }

    private function removeFunctionCallNodeIfNeeded(FuncCall|Node $node): void
    {
        foreach ($this->removedFunctions as $removedFunction) {
            if (!$this->isName($node->name, $removedFunction)) {
                continue;
            }

            // skip if the parent is an expression
            if ($this->betterNodeFinder->findParentType($node, Expr::class) !== null) {
                continue;
            }

            $this->removeNode($node);

            break;
        }
    }

    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allString($configuration);
        $this->removedFunctions = $configuration;
    }
}
