# Yet Another Github Visitor Counter

[![Tests](https://github.com/pa-ulander/ghvc/actions/workflows/tests.yml/badge.svg)](https://github.com/pa-ulander/ghvc/actions/workflows/tests.yml)
[![Test Coverage](./code_coverage_badge.svg)](https://github.com/pa-ulander/ghvc)
[![Deploy](https://github.com/pa-ulander/ghvc/actions/workflows/deploy.yml/badge.svg)](https://github.com/pa-ulander/ghvc/actions/workflows/deploy.yml)

A Laravel-based GitHub profile visitor counter that generates customizable SVG badges to display on your GitHub profile or repository README.

## Usage

Add this to your README.md to show a visitor counter badge:

```markdown
![](https://ghvc.kabelkultur.se?username=your-github-username)
```

It will generate a visitor counter badge that looks like this:

![](https://ghvc.kabelkultur.se/?username=pa-ulander&color=green&style=for-the-badge&label=Views)

## Badge Customization Options

The visitor counter badge can be customized with the following URL parameters:

| Parameter | Description | Default | Example Values |
|-----------|-------------|---------|---------------|
| `username` | GitHub username (required) | - | `username=octocat` |
| `label` | Text label displayed on the badge | Visits | `label=Profile Views` |
| `color` | Badge color | blue | `color=green`, `color=red`, `color=#FF5500` |
| `style` | Badge style | for-the-badge | `style=flat`, `style=flat-square`, `style=plastic` |
| `base` | Starting count value | 0 | `base=100` |
| `abbreviated` | Abbreviate large numbers | false | `abbreviated=true` |

## Examples

### Different Styles

**Flat Style**:
```markdown
![](https://ghvc.kabelkultur.se?username=your-github-username&style=flat)
```

**Flat Square Style**:
```markdown
![](https://ghvc.kabelkultur.se?username=your-github-username&style=flat-square)
```

**Plastic Style**:
```markdown
![](https://ghvc.kabelkultur.se?username=your-github-username&style=plastic)
```

### Custom Colors

**Named Colors**:
```markdown
![](https://ghvc.kabelkultur.se?username=your-github-username&color=brightgreen)
![](https://ghvc.kabelkultur.se?username=your-github-username&color=red)
![](https://ghvc.kabelkultur.se?username=your-github-username&color=orange)
![](https://ghvc.kabelkultur.se?username=your-github-username&color=yellow)
```

**Hex Colors**:
```markdown
![](https://ghvc.kabelkultur.se?username=your-github-username&color=#FF5500)
```

### Custom Labels

```markdown
![](https://ghvc.kabelkultur.se?username=your-github-username&label=Profile%20Visitors)
```

### Number Abbreviation

Display large numbers in abbreviated format (1K, 1.5M, etc.):

```markdown
![](https://ghvc.kabelkultur.se?username=your-github-username&abbreviated=true)
```

### Full Customization Example

```markdown
![](https://ghvc.kabelkultur.se?username=your-github-username&label=Visitors&color=orange&style=flat-square&abbreviated=true)
```

## Self-hosting

This is a Laravel-based application that can be self-hosted. See the project structure and Docker configuration for details on how to set up your own instance.

## License

[MIT](LICENSE)
