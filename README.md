# Naegleria

A [brainfuck](http://esolangs.org/wiki/Brainfuck) compiler written in PHP.

![](doc/naegleria.png)

> Naegleria fowleri /nəˈɡlɪəriə/ (also known as the "brain-eating amoeba") infects people by entering the body through the nose.

## Usage

You can use Naegleria to compile a brainfuck file into AT&T assembly. This can then be assembled using `gcc`, and executed directly.

    $ bin/compile examples/hello.b > hello.s
    $ gcc -o hello hello.s
    $ ./hello
    Hello World!

## Platforms

Targets 64-bit x86 on linux, specifically gcc.

## Limitations

The compiler uses a fixed-size array of 4000 elements for the cells.

## Optimizations

This is not (yet) an optimizing compiler.

## Acknowledgements

* Thanks to [@nikita_ppv](https://twitter.com/nikita_ppv) for planting the idea a long time ago.
* Thanks to [@codeoracle](https://twitter.com/codeoracle) for the inspiration to pick up `gcc -S`.
* Thanks to [@dazzlog](https://twitter.com/dazzlog) for helping understand assembly.
* Thanks to [@old_sound](https://twitter.com/old_sound) for coming up with the name and logo.
* Thanks to [the Esolang wiki](http://esolangs.org/) for being a great resource.
