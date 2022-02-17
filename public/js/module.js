/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

(function(Icinga) {

    "use strict";

    class Graphite extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this._colorParams = null;
            this._resizeTimer = null;
            this._onResizeBound = this.onResize.bind(this);
            this._onModeChangeBound = this.onModeChange.bind(this);
            this._mediaQueryList = window.matchMedia('(prefers-color-scheme: light)');

            this.on('css-reloaded', 'head', this.onCssReloaded, this);
            this.on('rendered', '#main > .container', this.onRendered, this);
            window.addEventListener('resize', this._onResizeBound, { passive: true });
            this._mediaQueryList.addEventListener('change', this._onModeChangeBound, { passive: true });
        }

        get colorParams() {
            if (this._colorParams === null) {
                let colorRegistry = document.querySelector('.graphite-graph-color-registry');
                let registryStyle = window.getComputedStyle(colorRegistry);

                this._colorParams = {
                    bgcolor: this.rgbToHex(registryStyle.backgroundColor, 'black'),
                    fgcolor: this.rgbToHex(registryStyle.color, 'white'),
                    majorGridLineColor: this.rgbToHex(registryStyle.borderTopColor, '0000003F'),
                    minorGridLineColor: this.rgbToHex(registryStyle.borderBottomColor, 'black')
                };
            }

            return this._colorParams;
        }

        unbind(emitter) {
            super.unbind(emitter);

            window.removeEventListener('resize', this._onResizeBound);
            this._mediaQueryList.removeEventListener('change', this._onModeChangeBound);

            this._onResizeBound = null;
            this._onModeChangeBound = null;
            this._mediaQueryList = null;
        }

        onCssReloaded(event) {
            let _this = event.data.self;

            _this._colorParams = null;
            _this.updateImages(document);
        }

        onRendered(event, autorefresh, scripted, autosubmit) {
            let _this = event.data.self;
            let container = event.target;

            _this.updateImages(container);
        }

        onResize() {
            // Images are not updated instantly, the user might not yet be finished resizing the window
            if (this._resizeTimer !== null) {
                clearTimeout(this._resizeTimer);
            }

            this._resizeTimer = setTimeout(() => this.updateImages(document), 200);
        }

        onModeChange() {
            this._colorParams = null;
            this.updateImages(document);
        }

        updateImages(container) {
            container.querySelectorAll('img.graphiteImg[data-actualimageurl]').forEach(img => {
                let params = { ...this.colorParams }; // Theming ftw!
                params.r = (new Date()).getTime(); // To bypass the browser cache
                params.width = img.scrollWidth; // It's either fixed or dependent on parent width

                img.src = this.icinga.utils.addUrlParams(img.dataset.actualimageurl, params);
            });
        }

        rgbToHex(rgb, def) {
            if (! rgb) {
                return def;
            }

            let match = rgb.match(/rgba?\((\d+), (\d+), (\d+)(?:, ([\d.]+))?\)/);
            if (match === null) {
                return def;
            }

            let alpha = '';
            if (typeof match[4] !== 'undefined') {
                alpha = Math.round(parseFloat(match[4]) * 255).toString(16);
            }

            return parseInt(match[1], 10).toString(16).padStart(2, '0')
                + parseInt(match[2], 10).toString(16).padStart(2, '0')
                + parseInt(match[3], 10).toString(16).padStart(2, '0')
                + alpha;
        }
    }

    Icinga.Behaviors.Graphite = Graphite;

})(Icinga);