# Yet Another Github Visitor Counter

[![Tests](https://github.com/pa-ulander/ghvc/actions/workflows/tests.yml/badge.svg)](https://github.com/pa-ulander/ghvc/actions/workflows/tests.yml)
[![Test Coverage](./code_coverage_badge.svg)](https://github.com/pa-ulander/ghvc)
[![Deploy](https://github.com/pa-ulander/ghvc/actions/workflows/deploy.yml/badge.svg)](https://github.com/pa-ulander/ghvc/actions/workflows/deploy.yml)

A Laravel-based GitHub profile visitor conuter and github repository visitor counter that generates customizable SVG badges to display on your GitHub profile or repository's README.<br> 
Made only for fun and to try out new latest laravel and testing features. Sorry I couldn't resist, I also just wanted to have a visitorcounter on my github profile.

## Usage

Add this to your profile page README.md to show a visitor counter badge:

```markdown
![](https://ghvc.kabelkultur.se?username=your-github-username)
```

It will generate a visitor counter badge that looks like this:

![](https://ghvc.kabelkultur.se/?username=pa-ulander&color=green&style=for-the-badge&label=Views)


## Usage per repository

Add this to your repository README.md to show a visitor counter badge for a specific repository:

![](https://ghvc.kabelkultur.se/?username=pa-ulander&color=green&style=for-the-badge&repos=name-of-my-github-repository&label=Repository%20Views)

## Badge Customization Options

The visitor counter badge can be customized with the following URL parameters:

| Parameter | Description | Default | Example Values |
|-----------|-------------|---------|---------------|
| `username` | GitHub username (required) | - | `username=octocat` |
| `label` | Text label displayed on the badge | Visits | `label=Profile Views` |
| `color` | Badge color | blue | `color=green`, `color=red`, `color=FF5500` |
| `style` | Badge style | for-the-badge | `style=flat`, `style=flat-square`, `style=plastic` |
| `base` | Starting count value | 0 | `base=100` |
| `abbreviated` | Abbreviate large numbers | false | `abbreviated=true` |


## Examples

### Different Styles

| Style | Example |
|------------|------------|
| `for-the-badge` | ![](https://ghvc.kabelkultur.se?username=your-github-username&style=for-the-badge) |
| `flat` | ![](https://ghvc.kabelkultur.se?username=your-github-username&style=flat) |
| `flat-square` | ![](https://ghvc.kabelkultur.se?username=your-github-username&style=flat-square) |
| `plastic` | ![](https://ghvc.kabelkultur.se?username=your-github-username&style=plastic) |


### Custom Colors

| **Named Color** | Example |
|------------|------------|
| `brightgreen` | ![](https://ghvc.kabelkultur.se?username=your-github-username&color=brightgreen) |
| `red` | ![](https://ghvc.kabelkultur.se?username=your-github-username&color=red) |
| `orange` | ![](https://ghvc.kabelkultur.se?username=your-github-username&color=orange) |
| `yellow` | ![](https://ghvc.kabelkultur.se?username=your-github-username&color=yellow) |



| **Hex Color** | Example |
|------------|------------|
| `f000ff` | ![](https://ghvc.kabelkultur.se?username=your-github-username&color=f000ff) |
| `bff000` | ![](https://ghvc.kabelkultur.se?username=your-github-username&color=bff000) |
| `e45a18` | ![](https://ghvc.kabelkultur.se?username=your-github-username&color=a45a18) |
| `d558e0` | ![](https://ghvc.kabelkultur.se?username=your-github-username&color=d558e0) |

> **Note:** You must specify hex colors without the `#` prefix (e.g., `f000ff` instead of `#f000ff`).

| **Custom Label** | Example |
|------------|------------|
| `Profile%20Visitors` | ![](https://ghvc.kabelkultur.se?username=your-github-usernamelabel=Profile%20Visitors) |
| `Chocolate%20Cookies` | ![](https://ghvc.kabelkultur.se?username=your-github-username&label=Chocolate%20Cookies) |
| `Horsepowers` | ![](https://ghvc.kabelkultur.se?username=your-github-username&label=Horsepowers) |


### Number Abbreviation

Display large numbers in abbreviated format (1K, 1.5M, etc.):

```markdown
![](https://ghvc.kabelkultur.se?username=your-github-username&abbreviated=true)
```
![](https://ghvc.kabelkultur.se?username=your-github-username&&abbreviated=true) 


### Full Customization Example

```markdown
![](https://ghvc.kabelkultur.se?username=your-github-username&label=Visitors&color=orange&style=flat-square&abbreviated=true)
```
![](https://ghvc.kabelkultur.se?username=your-github-username&label=Visitors&color=orange&style=flat-square&abbreviated=true)
## Self-hosting

This is a Laravel-based application that can be self-hosted. See the project structure and Docker configuration for details on how to set up your own instance.

## Acknowledgments
- [Badges](https://github.com/badges) for the cool [Poser](https://github.com/badges/poser) library. A php library that creates badges.

- [Awesome Badges](https://github.com/badges/awesome-badges) a curated list of awesome badge things. 


## License

[MIT](LICENSE)
