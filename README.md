# Yet Another Github Visitor Counter

[![Tests](https://github.com/pa-ulander/ghvc/actions/workflows/tests.yml/badge.svg)](https://github.com/pa-ulander/ghvc/actions/workflows/tests.yml)[![Test Coverage](./code_coverage_badge.svg)](https://github.com/pa-ulander/ghvc)[![Deploy](https://github.com/pa-ulander/ghvc/actions/workflows/deploy.yml/badge.svg)](https://github.com/pa-ulander/ghvc/actions/workflows/deploy.yml)![](https://ghvc.kabelkultur.se?username=pa-ulander&label=Repository%20visits&color=brightgreen&style=flat&repository=yaghvc)

A Laravel-based GitHub profile visitor counter and github repository visitor counter that generates customizable SVG badges to display on your GitHub profile or in a repository's README.  
  
Made only for fun and to try out new latest laravel and testing features. I just wanted to have a visitorcounter on my github profile.

> Developers: See the new `DEVELOPMENT.md` guide for local setup, contributing, testing and architecture details.

## Usage

Add this to your profile page README.md to show a visitor counter badge:

```
![](https://ghvc.kabelkultur.se?username=your-username)
```

It will generate a visitor counter badge that looks like this:

![](./public_html/assets/default.svg)

## Usage per repository

Add this to your repository README.md to show a visitor counter badge for a specific repository:

```
![](https://ghvc.kabelkultur.se/?username=your-username&repository=my-repository&label=my-repository%20Views)
```

![](./public_html/assets/repository.svg)

## Badge Customization Options

The visitor counter badge can be customized with the following URL parameters:

| Parameter | Description | Default | Example Values |
| --- | --- | --- | --- |
| `username` | GitHub username (required) | \- | `username=your-username` |
| `label` | Text label displayed on the badge | Visits | `label=Profile Views` |
| `color` | Badge right-side (value) color | blue | `color=green`, `color=red`, `color=ff5500` |
| `labelColor` | Left-side label background color (named or hex w/out #) | blue | `labelColor=red`, `labelColor=ffd700` |
| `style` | Badge style | for-the-badge | `style=flat`, `style=flat-square`, `style=plastic` |
| `base` | Starting count value added to stored counter | 0 | `base=100` |
| `abbreviated` | Abbreviate large numbers (1.2K, 3.4M) | false | `abbreviated=true` |
| `repository` | Count visits in a repository (scopes counter) | (none) | `repository=my-repo` |
| `logo` | Data URI image (png,jpg,gif,svg) OR simple-icons slug | (none) | `logo=github`, `logo=laravel`, `logo=data:image/png;base64,iVBOR...` |
| `logoSize` | Logo sizing: 'auto' (SVG adapt) or fixed px (8-64) | 14 | `logoSize=auto`, `logoSize=32` |
| `logoColor` | Recolor SVG/simple-icon logo (named or hex, no #) | (none) | `logoColor=red`, `logoColor=ff8800`, `logoColor=brightgreen` |

## Examples

### Different Styles

| Style | Example | Markdown |
| --- | --- | --- |
| `for-the-badge` | ![](./public_html/assets/style-for-the-badge.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&style=for-the-badge)` |
| `flat` | ![](./public_html/assets/style-flat.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&style=flat)` |
| `flat-square` | ![](./public_html/assets/style-flat-square.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&style=flat-square)` |
| `plastic` | ![](./public_html/assets/style-plastic.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&style=plastic)` |

### Custom Colors

| **Named Color** | Example | Markdown |
| --- | --- | --- |
| `brightgreen` | ![](./public_html/assets/color-green.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=brightgreen)` |
| `red` | ![](./public_html/assets/color-red.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=red)` |
| `orange` | ![](./public_html/assets/color-orange.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=orange)` |
| `yellow` | ![](./public_html/assets/color-yellow.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=yellow)` |

| **Hex Color** | Example | Markdown |
| --- | --- | --- |
| `ffd700` | ![](./public_html/assets/hex-ffd700.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=ffd700)` |
| `e34234` | ![](./public_html/assets/hex-e34234.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=e34234)` |
| `6a0dad` | ![](./public_html/assets/hex-6a0dad.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=6a0dad)` |
| `00b7eb` | ![](./public_html/assets/hex-00b7eb.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&color=00b7eb)` |

> **Note:** You must specify hex colors without the `#` prefix (e.g., `f000ff` instead of `#f000ff`).

### Custom Labels

| **Custom Label** | Example | Markdown |
| --- | --- | --- |
| `Profile%20Visitors` | ![](./public_html/assets/label-pfv.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&label=Profile%20Visitors)` |
| `Chocolate%20Cookies` | ![](./public_html/assets/label-cho.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&label=Chocolate%20Cookies)` |
| `Horsepowers` | ![](./public_html/assets/label-hp.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&label=Horsepowers)` |


### Custom Label Color

| **Label Color** | Example | Markdown |
| --- | --- | --- |
| `red` | ![](./public_html/assets/labelColor-pfv.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&labelColor=red)` |
| `green` | ![](./public_html/assets/labelColor-cho.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&&=Chocolate%20Cookies&labelColor=green)` |
| `brown` | ![](./public_html/assets/labelColor-hp.svg) | `![](https://ghvc.kabelkultur.se?username=your-username&label=Horsepowers&labelColor=brown)` |



### Label Background Color (labelColor)

You can style the left label segment independently from the value segment using `labelColor`:

```
![](https://ghvc.kabelkultur.se?username=your-username&labelColor=0000ff)
```
![](http://localhost?username=your-username&labelColor=00aaff)


### Number Abbreviation

Display large numbers in abbreviated format (1K, 1.5M, etc.):

```
![](https://ghvc.kabelkultur.se?username=your-username&abbreviated=true)
```

![](./public_html/assets/abbr.svg)

### Full Customization Example

```
![](https://ghvc.kabelkultur.se?username=your-username&label=Visitors&color=orange&style=for-the-badge&abbreviated=true)
```

![](./public_html/assets/full.svg)



# Logo or icon usage

The `logo` parameter supports either a simple-icons slug or a full base64 data URI.  
The data URI may or may not be urlencoded.  
It's good practise to use a urlencoded base64 data URI.  
But you don't have to. 
You **can** just use a full svg or png data URI.

---

### Simple icon slug example:

```
![](https://ghvc.kabelkultur.se?username=your-username&logo=github)
```
![](http://localhost/?username=your-username&logo=github)  

If you are use a simpleicon slug you can also set logoColor.  
Default logoColor when simple icon slugs is f5f5f5 (as seen above with the github icon). Which is what you get if you don't set a logoColor.

#### Example with logoColor set to orange:

```
![](https://ghvc.kabelkultur.se?username=your-username&logo=github&logoColor=orange)
```
![](http://localhost/?username=your-username&logo=github&logoColor=orange)



Examples:

```
```

### Logo Size

When using logo, you can also set logoSize 



 

Small PNG via data URI:

```
![](https://ghvc.kabelkultur.se?username=your-username&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==)
```

![](http://localhost/?username=your-username&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==)

SVG with automatic aspect scaling:

```
![](https://ghvc.kabelkultur.se?username=your-username&logo=<your-encoded-svg-data-uri>&logoSize=auto)
```

Sizing:

```
logoSize=auto   # scale width to maintain intrinsic aspect ratio at target height
logoSize=32     # fixed square size (clamped to configured max)
```


### Logo Color (logoColor)

`logoColor` lets you recolor supported SVG logos (simple-icons slugs or inline SVG data URIs). It is ignored for raster images (png/jpg/gif) and for SVGs where a safe unified replacement cannot be determined. If you omit `logoColor` for a simple-icons slug, a subtle neutral default `f5f5f5` is applied automatically.

Accepted formats mirror `color` / `labelColor`:



## Self-hosting

This is a Laravel-based application that can be self-hosted.  
Consult [DEVELOPMENT.md](./DEVELOPMENT.md) for some technical documentation. 

## Contributing

Feature suggestions, code reviews and contributions are welcome!

## Acknowledgments

- [Badges](https://github.com/badges) for the cool [Poser](https://github.com/badges/poser) library. A php library that creates badges.

- [Awesome Badges](https://github.com/badges/awesome-badges) a curated list of awesome badge things.

- [Simple Icons](https://github.com/simple-icons/simple-icons) SVG icons for popular brands.


## License

[MIT](LICENSE)