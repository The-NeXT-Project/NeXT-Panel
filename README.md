<img src=".github/next_1000.png" alt="next" width="600"/>

[![X (formerly Twitter)](https://img.shields.io/twitter/url?url=https%3A%2F%2Ftwitter.com%2FSSPanel_NeXT)](https://twitter.com/SSPanel_NeXT)
[![Discord](https://img.shields.io/discord/1049692075085549600?color=5865F2&label=Discord&style=flat-square)](https://discord.gg/A7uFKCvf8V)

## PSA


1. We are looking for a new paid co-maintainer for this project who is familiar with PHP
and can speak Persian/Arabic/Russian/Vietnamese to help with NeXT Panel's I18n translation effort.
If you are interested in helping out, please contact us on Discord or Twitter.

2. Regarding commit history and source code, we recently noticed an unauthorized redistribution attempt, which may draw unnecessary attention to the NeXT Panel project, for the longevity of SSPanel-NeXT and its projects, we have decided we will no longer publish git commit history. The newer release of the NeXT panel will contain a zip file that includes the project's source code.

3. The only official repo of the NeXT panel is [SSPanel-NeXT/NeXT-Panel](https://github.com/SSPanel-NeXT/NeXT-Panel) on GitHub, we don't distribute our project's code anywhere else and you should NOT download source code archive from 3rd party website/repo because we can't guarantee it's integrity and security.

## TL;DR

NeXT Panel (OSS Edition) is a multipurpose proxy service management system designed for Shadowsocks(2022) / Vmess / Trojan / TUIC protocol.

## About Pro version

NeXT Panel (Pro Edition) is a work-in-progress version of the original OSS Edition, it replaced many of its PHP-based backends with high-performance Golang-based ones, and significantly improved site response time under heavy load.

The Pro version will use a yearly subscription model, we plan to provide a dedicated license purchase site and existing patron members can access it as well. We will publish further pricing info on our [Discord server](https://discord.gg/A7uFKCvf8V) and [Twitter account](https://twitter.com/SSPanel_NeXT), please stay tuned.

## Feature Comparison(OSS vs Pro)

| Feature                                                                                                                   | OSS Edition | Pro Edition |
|---------------------------------------------------------------------------------------------------------------------------|-------------|-------------|
| Core PHP Backend & Htmx/jQuery Frontend                                                                                   | ✅           | ✅           |
| Golang-based high-performance Node/User/Admin API                                                                         | ❌           | ✅(WiP)           |
| Golang-based high-performance statistical API that can support real-time client-side updates & server events              | ❌           | ✅(WiP)           |
| Access to over the air(OTA) service that provides one-click software update                                               | ❌           | ✅(WiP)         |
| Access to our experimental risk management API that can filter out potential spam/malicious/abusing users                 | ❌           | ✅(WiP)           |
| Access to our prebuilt Docker Image repository that supports frictionless site setup/update/migration experience          | ❌           | ✅           |
| Support for PostgreSQL in addition to the currently supported MariaDB as the main database                                | ❌           | ✅           |
| Easy to use panel initialization wizard, no CLI operation is needed                                                       | ❌           | ✅           |
| Integration with other cluster management system(Ansible/SaltStack), automatically manage your proxy servers in one place | ❌           | ✅(WiP)           |

## Installation

NeXT Panel requires the following programs to be installed and run normally:

- Nginx（HTTPS configured）
- PHP 8.2+ （OPcache+JIT enabled）
- PHP Redis extension 6.0+
- MariaDB 10.11+（Disable strict mode）
- Redis 7.0+

## Ecosystem

- [NeXT Server](https://github.com/SSPanel-NeXT/NeXT-Server)
- NeXT Desktop(WiP)
- [NetStatus-API-Go](https://github.com/SSPanel-NeXT/NetStatus-API-Go)

## Documentation

[NeXT Panel Docs](https://nextpanel.dev)

## Support

<a href="https://www.patreon.com/catdev">Patreon (One time or monthly)</a>

<a href="https://www.vultr.com/?ref=8941355-8H">Vultr Ref Link</a>

<a href="https://www.digitalocean.com/?refcode=50f1a3b6244c">DigitalOcean Ref Link</a>

## License

[GPL-3.0 License](blob/dev/LICENSE)

## Star History

[![Star History Chart](https://api.star-history.com/svg?repos=SSPanel-NeXT/NeXT-Panel&type=Date)](https://star-history.com/#SSPanel-NeXT/NeXT-Panel&Date)
