<?php

declare(strict_types=1);

namespace Dev\Smarty\Comments;

use Dev\Smarty\Ast\AnnotationNode;
use Dev\Smarty\Ast\Position;
use Dev\Smarty\Ast\SourceSpan;

final class PhpDocTemplateAnnotationParser implements CommentParserInterface
{
    public function parse(\Dev\Smarty\Ast\CommentNode $comment, CommentParseContext $context): CommentParseResult
    {
        $text = trim($comment->text);
        if ($text === '') {
            return new CommentParseResult();
        }

        $lines = preg_split('/\R/', $text) ?: [];
        $annotations = [];

        foreach ($lines as $lineOffset => $line) {
            $clean = trim(preg_replace('/^\*\s?/', '', trim($line)) ?? '');
            if ($clean === '' || !str_starts_with($clean, '@')) {
                continue;
            }

            if (!preg_match('/^@(?P<tag>[a-zA-Z_\\-]+)\s*(?P<body>.*)$/', $clean, $match)) {
                continue;
            }

            $tag = strtolower($match['tag']);
            $body = trim($match['body']);
            $payload = ['raw' => $body];

            if ($tag === 'var') {
                if (preg_match('/^(?P<type>[^\s]+)\s+\$(?P<name>[A-Za-z_][A-Za-z0-9_]*)\s*(?P<description>.*)$/', $body, $varMatch)) {
                    $payload = [
                        'type' => $varMatch['type'],
                        'name' => $varMatch['name'],
                        'description' => trim($varMatch['description']),
                    ];
                }
            }

            if ($tag === 'param') {
                if (preg_match('/^(?P<type>[^\s]+)\s+\$(?P<name>[A-Za-z_][A-Za-z0-9_]*)\s*(?P<description>.*)$/', $body, $paramMatch)) {
                    $payload = [
                        'type' => $paramMatch['type'],
                        'name' => $paramMatch['name'],
                        'description' => trim($paramMatch['description']),
                    ];
                }
            }

            $start = new Position(
                $comment->span->start->offset,
                $comment->span->start->line + $lineOffset,
                1,
            );
            $end = new Position(
                $comment->span->start->offset,
                $comment->span->start->line + $lineOffset,
                max(1, strlen($line)),
            );

            $annotations[] = new AnnotationNode(
                new SourceSpan($start, $end),
                $tag,
                $payload,
            );
        }

        return new CommentParseResult($annotations, []);
    }
}
