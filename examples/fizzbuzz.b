[
    this is almost fizzbuzz; it does not print asciified numbers; because that would take way too much work
    instead; it just outputs F for fizz numbers; B for buzz numbers and a dot for the others;
    enjoy;

    thanks to the following resources:

    * http://esolangs.org/wiki/Brainfuck
    * http://esolangs.org/wiki/Brainfuck_constants
    * http://esolangs.org/wiki/Brainfuck_algorithms
]

initialize counter to 100 (it counts down)

    ++++++++++[>++++++++++<-]
    >[-<+>]<

open outer loop

    [>

initialize n to be 1

    +

setup divmod 3
_n_ 0 d

    >>
    +++
    <<

divmod

    [->+>-[>+>>]>[+[-<+>]>+>>]<<<<<<]

shift n back to the left
from offset 1 to offset 0

    >[-<+>]<

clean up offsets 2 and 4

    >>
    [-]
    >>
    [-]
    <<<<

modulo is at offset 3
if it is zero; print fizz

    >[-]+
    >[-]
    >[<<->>[<+>-]]
    <[>+<-]
    <[
        >>>
            70 is ascii F
            +++++++[>++++++++++<-]>
            .
            [-]<[-]

            set flag at offset 7
            >>>[-]+<<<
        <<<
    -]<

cleanup offset 3

    >>>[-]<<<

******************************

setup divmod 5
_n_ 0 d

    >>
    +++++
    <<

divmod

    [->+>-[>+>>]>[+[-<+>]>+>>]<<<<<<]

shift n back to the left
from offset 1 to offset 0

    >[-<+>]<

clean up offsets 2 and 4

    >>
    [-]
    >>
    [-]
    <<<<

modulo is at offset 3
if it is zero; print buzz

    >[-]+
    >[-]
    >[<<->>[<+>-]]
    <[>+<-]
    <[
        >>>
            66 is ascii B
            ++++++[>+++++++++++<-]>
            .
            [-]<[-]

            set flag at offset 7
            >>>[-]+<<<
        <<<
    -]<

cleanup offset 3

    >>>[-]<<<

******************************

check flag at offset 7
if it is zero; we need to output the current number
or at least pretend to

    >>>>>
    [-]+
    >[-]
    >[<<->>[<+>-]]
    <[>+<-]
    <[
        >>>
            46 is ascii dot
            +++++[>+++++++++<-]>+
            .
            [-]<[-]
        <<<
    -]
    <<<<<

unset flag

    >>>>>>>[-]<<<<<<<

******************************

    >
    10 is ascii \n
    ++++++++++
    .
    [-]
    <

close outer loop

    <-]

