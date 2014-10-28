<?php

namespace igorw\naegleria;

function tokenize($input) {
    $tokens = str_split($input);
    $tokens = array_values(array_filter($tokens, function ($token) {
        return in_array($token, ['>', '<', '+', '-', '.', ',', '[', ']'], true);
    }));
    return $tokens;
}

function parse($tokens) {
    $i = 0;
    while ($i < count($tokens)) {
        $token = $tokens[$i];
        $i++;

        switch ($token) {
            case '>';
                yield ['T_RIGHT', 1];
                break;
            case '<';
                yield ['T_LEFT', 1];
                break;
            case '+';
                yield ['T_INC', 1];
                break;
            case '-';
                yield ['T_DEC', 1];
                break;
            case '.';
                yield ['T_OUTPUT'];
                break;
            case ',';
                yield ['T_INPUT'];
                break;
            case '[';
                list($nodes, $i) = extract_loop($tokens, $i);
                $subtree = parse($nodes);
                yield ['T_LOOP', $subtree];
                break;
            case ']';
                // noop
                break;
        }
    }
}

function extract_loop($tokens, $i) {
    $nodes = [];
    $nesting = 1;
    while ($nesting > 0) {
        $token = $tokens[$i];
        $i++;

        if ('[' === $token) {
            $nesting++;
        } else if (']' === $token) {
            $nesting--;
        }

        if ($nesting > 0) {
            $nodes[] = $token;
        }
    }
    return [$nodes, $i];
}

function optimize($ast) {
    return optimize_compact($ast);
}

function optimize_compact($ast) {
    $accNode = null;
    foreach (pairwise($ast) as list($node, $next)) {
        $type = $node[0];

        if ($type === 'T_LOOP') {
            yield ['T_LOOP', optimize_compact($node[1])];
            $accNode = null;
            continue;
        }

        $compactable = in_array($type, ['T_RIGHT', 'T_LEFT', 'T_INC', 'T_DEC'], true);

        if ($compactable && $next && $next[0] === $type) {
            $accNode = $accNode ? [$type, $accNode[1] + $node[1]] : $node;
            continue;
        }

        if ($accNode && $accNode[0] === $type) {
            yield [$type, $accNode[1] + $node[1]];
            $accNode = null;
            continue;
        }

        if ($accNode) {
            yield $accNode;
            $accNode = null;
        }

        yield $node;
    }
}

function pairwise($gen) {
    $prev = null;
    foreach ($gen as $item) {
        if (!$prev) {
            $prev = $item;
            continue;
        }
        yield [$prev, $item];
        $prev = $item;
    }
    if ($prev) {
        yield [$prev, null];
    }
}

function force($gen) {
    $data = [];
    foreach ($gen as $item) {
        if ($item instanceof \Traversable || is_array($item)) {
            $data[] = force($item);
            continue;
        }
        $data[] = $item;
    }
    return $data;
}

function compile($ast, $prefix = '') {
    $condId = 0;
    $loopId = 0;
    foreach ($ast as $node) {
        $type = $node[0];
        switch ($type) {
            case 'T_RIGHT';
                yield ' # >';
                yield ' movq    i(%rip), %rax';
                yield ' addq    $'.$node[1].', %rax';
                yield ' movq    %rax, i(%rip)';
                break;
            case 'T_LEFT';
                yield ' # <';
                yield ' movq    i(%rip), %rax';
                yield ' subq    $'.$node[1].', %rax';
                yield ' movq    %rax, i(%rip)';
                break;
            case 'T_INC';
                yield ' # +';
                yield ' movq    i(%rip), %rax';
                yield ' movzbl  (%rax), %edx';
                yield ' addl    $'.$node[1].', %edx';
                yield ' movb    %dl, (%rax)';
                break;
            case 'T_DEC';
                yield ' # -';
                yield ' movq    i(%rip), %rax';
                yield ' movzbl  (%rax), %edx';
                yield ' subl    $'.$node[1].', %edx';
                yield ' movb    %dl, (%rax)';
                break;
            case 'T_OUTPUT';
                yield ' # .';
                yield ' movq    i(%rip), %rax';
                yield ' movzbl  (%rax), %eax';
                yield ' movsbl  %al, %eax';
                yield ' movl    %eax, %edi';
                yield ' call    putchar';
                break;
            case 'T_INPUT';
                $condId++;
                yield ' # ,';
                yield ' movq    i(%rip), %rbx';
                yield ' call    getchar';
                yield ' movb    %al, (%rbx)';
                yield ' movq    i(%rip), %rax';
                yield ' movzbl  (%rax), %eax';
                yield ' cmpb    $4, %al';
                yield " jne .cond$prefix$condId";
                yield ' movq    i(%rip), %rax';
                yield ' movb    $0, (%rax)';
                yield ".cond$prefix$condId:";
                break;
            case 'T_LOOP';
                $loopId++;
                yield ' # [';
                yield ".loops$prefix$loopId:";
                yield ' movq    i(%rip), %rax';
                yield ' movzbl  (%rax), %eax';
                yield ' cmpb    $0, %al';
                yield " je  .loope$prefix$loopId";
                foreach (compile($node[1], $loopId.'_') as $instr) {
                    yield $instr;
                }
                yield ' # ]';
                yield " jmp .loops$prefix$loopId";
                yield ".loope$prefix$loopId:";
                break;
        }
    }
}

$template = <<<'EOF'
    .comm   tape,4000,32
    .globl  i
    .data
    .align 8
    .type   i, @object
    .size   i, 8
i:
    .quad   tape
    .section    .rodata

.stty:
    .string "stty -icanon"
    .text

    .globl  main
    .type   main, @function
main:
    .cfi_startproc
    pushq   %rbp
    .cfi_def_cfa_offset 16
    .cfi_offset 6, -16
    movq    %rsp, %rbp
    .cfi_def_cfa_register 6

    movl    $.stty, %edi
    call    system

$code
    movl    $0, %eax
    popq    %rbp
    .cfi_def_cfa 7, 8
    ret
    .cfi_endproc
EOF;

if ($argc <= 1) {
    echo "Usage: compile filename.b\n";
    exit(1);
}

$filename = $argv[1];
$tokens = tokenize(file_get_contents($filename));
$ast = parse($tokens);
$ast = optimize($ast);

$code = '';
foreach (compile($ast) as $instr) {
    $code .= $instr."\n";
}
echo str_replace('$code', $code, $template);
echo "\n";
