# Rephó

A lightweight repository manager and proxy for composer.

> [!NOTE]
> For enterprise and medium/large projects, I recommend using [Private Packagist](https://packagist.com/).

## Tell me more

Repho is designed to simplify the management of composer repositories, providing a streamlined way to host and distribute packages. It offers a simple and efficient solution for developers who need to manage small composer repositories without the complexity of full-fledged package managers.

## For whom?

- For developers that need to host a few packages on a private server.
- For offline environments. (You are developing a package during your plane/train trip, and you need to pull cached packages from cached by the proxy or from a local repository).


## Features

- Simple to setup.
- Simple to use.
- Doesn't require any RDMS, it can work only with a sqlite file.
- Ideal for small projects and/or small teams.
- Optional composer proxy.
- Security audit provided by [Packagist API](https://packagist.org/security-advisories/).
- It can keep copies of packages in a local directory.
- Slack and email notifications for security alerts.
- Lightweight, you can host it on a Raspberry Pi or the cheaper Laravel Cloud instance.
- It may be configured to work without running a queue worker


## Notes

This project is under development and mostly code was generated with Claude code, so it's not perfect, and it has a lot of bugs. Do not use on production environments.
