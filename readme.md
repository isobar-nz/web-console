# SilverStripe web-console

Forked from the great [web console](https://github.com/nickola/web-console) project,
and turned into a silverstripe CMS project.

## Install

`composer require isobar-nz/web-console ^2.1`

## How it works

It acts like a simple SSH shell, but in a CMS admin panel.

Useful for web hosts that give you shared or web-only access, but
no ssh or other console.

Unfortunately you don't get any interactive shell functionality (e.g. nano),
but you can do most single line commands.

## Running long-commands

Typically commands will be sent to the server, be triggered, and return
the result once it's completed.

If you have a long running task, then instead you should use 'stream' command
to stream the task in the background. When using this command, the task
will be run in a separate process, and the frontend javascript will poll
the server for output (don't refresh the page while this is running though!).

run `stream --help` in the console for help.

Note: You don't need to install crontask, as webconsole has it's own
process control based on Symfony.
