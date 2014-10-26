<?php

namespace igorw\naegleria;

if ($argc <= 1) {
    echo "Usage: compile filename.b\n";
    exit(1);
}

$filename = $argv[1];

$tokens = str_split(file_get_contents($filename));
$tokens = array_values(array_filter($tokens, function ($token) {
    return in_array($token, ['>', '<', '+', '-', '.', ',', '[', ']'], true);
}));

function parse($tokens) {
    $condId = 0;
    $loopId = 0;
    $loopStack = [];
    foreach ($tokens as $token) {
        switch ($token) {
            case '>';
                yield ' # >';
                yield ' movq    i(%rip), %rax';
                yield ' addq    $1, %rax';
                yield ' movq    %rax, i(%rip)';
                break;
            case '<';
                yield ' # <';
                yield ' movq    i(%rip), %rax';
                yield ' subq    $1, %rax';
                yield ' movq    %rax, i(%rip)';
                break;
            case '+';
                yield ' # +';
                yield ' movq    i(%rip), %rax';
                yield ' movzbl  (%rax), %edx';
                yield ' addl    $1, %edx';
                yield ' movb    %dl, (%rax)';
                break;
            case '-';
                yield ' # -';
                yield ' movq    i(%rip), %rax';
                yield ' movzbl  (%rax), %edx';
                yield ' subl    $1, %edx';
                yield ' movb    %dl, (%rax)';
                break;
            case '.';
                yield ' # .';
                yield ' movq    i(%rip), %rax';
                yield ' movzbl  (%rax), %eax';
                yield ' movsbl  %al, %eax';
                yield ' movl    %eax, %edi';
                yield ' call    putchar';
                break;
            case ',';
                $condId++;
                yield ' # ,';
                yield ' movq    i(%rip), %rbx';
                yield ' call    getchar';
                yield ' movb    %al, (%rbx)';
                yield ' movq    i(%rip), %rax';
                yield ' movzbl  (%rax), %eax';
                yield ' cmpb    $4, %al';
                yield " jne .cond$condId";
                yield ' movq    i(%rip), %rax';
                yield ' movb    $0, (%rax)';
                yield ".cond$condId:";
                break;
            case '[';
                $loopId++;
                $loopStack[] = $loopId;
                yield ' # [';
                yield ".loops$loopId:";
                yield ' movq    i(%rip), %rax';
                yield ' movzbl  (%rax), %eax';
                yield ' cmpb    $0, %al';
                yield " je  .loope$loopId";
                break;
            case ']';
                $endLoopId = array_pop($loopStack);
                yield ' # ]';
                yield " jmp .loops$endLoopId";
                yield ".loope$endLoopId:";
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

    movl    $4000, %edx
    movl    $0, %esi
    movl    $tape, %edi
    call    memset

$code
    movl    $0, %eax
    popq    %rbp
    .cfi_def_cfa 7, 8
    ret
    .cfi_endproc
EOF;

$code = '';
foreach (parse($tokens) as $instr) {
    $code .= $instr."\n";
}
echo str_replace('$code', $code, $template);
echo "\n";
