
(function(Icinga) {

    var Graphite = function(module) {
        /**
         * YES, we need Icinga
         */
        this.module = module;

        this.imgClones = {
            'col1': [],
            'col2': []
        };

        this.lastImgId = 0;

        this.initialize();

        this.timer;

        this.module.icinga.logger.debug('Graphite module loaded');
    };

    Graphite.prototype = {

        initialize: function()
        {
            this.module.on('rendered', this.onRenderedContainer);
            this.registerTimer();
            this.module.icinga.logger.debug('Graphite module initialized');
        },

        registerTimer: function () {
            this.timer = this.module.icinga.timer.register(
                this.timerTriggered,
                this,
                8000
            );

            return this;
        },

        timerTriggered: function () {
            /// console.log('Graphite timer fired');
            var self = this;
            $.each(this.imgClones, this.reloadContainerImgs.bind(self));
        },

        reloadContainerImgs: function(idx, imgs)
        {
            $.each(imgs, this.reloadImg);
        },

        reloadImg: function(idx, img)
        {
            // console.log('Schedule reload for ', img);
            var realId = img.attr('id').replace(/_clone$/, '');
            $('#' + realId).attr('src', img.attr('src'));
            img.attr(
                'src',
                img.attr('src').replace(
                    /\&r=\d+/,
                    '&r=' + (new Date()).getTime()
                )
            );
        },

        onRenderedContainer: function(event) {
            var $container = $(event.currentTarget);
            var self = this;
            var cId = $container.attr('id');
            self.imgClones[cId] = [];
            $('#' + cId + ' img.graphiteImg').each(function(idx, img) {
              var $img = $(img);
              if (! $img.attr('id')) {
                self.lastImgId++;
                $(img).attr('id', 'graphiteImg' + self.lastImgId);
              }

              self.imgClones[cId].push(
                $(
                    $img.clone()
                        .addClass('graphiteClone')
                        .attr('id', $img.attr('id') + '_clone')
                        .load(self.imageLoaded)
                        // .data('
                        .attr(
                            'src',
                            $img.attr('src') + '&r=' + (new Date()).getTime()
                        )

                )
              );
            });
        },

        imageLoaded: function (event) {
            // console.log('LOADED', event);
        }
    };

    Icinga.availableModules.graphite = Graphite;

}(Icinga));

(function(Icinga, $) {
    'use strict';

    var extractUrlParams = /^([^?]*)\?(.+)$/;
    var parseUrlParam = /^([^=]+)=(.*)$/;

    function GraphiteCachebusterUpdater(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', this.onRendered, this);
    }

    GraphiteCachebusterUpdater.prototype = new Icinga.EventListener();

    GraphiteCachebusterUpdater.prototype.onRendered = function(event) {
        $(event.target).find('img.graphiteImg').each(function() {
            var e = $(this);
            var src = e.attr('src');

            if (typeof(src) !== 'undefined') {
                var matchParams = extractUrlParams.exec(src);

                if (matchParams !== null) {
                    var urlParams = Object.create(null);

                    matchParams[2].split('&').forEach(function(urlParam) {
                        var matchParam = parseUrlParam.exec(urlParam);
                        if (matchParam !== null) {
                            urlParams[matchParam[1]] = matchParam[2];
                        }
                    });

                    if (typeof(urlParams.cachebuster) !== 'undefined') {
                        var cachebuster = parseInt(urlParams.cachebuster);

                        if (cachebuster === cachebuster) {
                            urlParams.cachebuster = (cachebuster + 1).toString();

                            var renderedUrlParams = [];

                            for (var urlParam in urlParams) {
                                renderedUrlParams.push(urlParam + '=' + urlParams[urlParam]);
                            }

                            e.attr('src', matchParams[1] + '?' + renderedUrlParams.join('&'));
                        }
                    }
                }
            }
        });
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.GraphiteCachebusterUpdater = GraphiteCachebusterUpdater;
}(Icinga, jQuery));

(function(Icinga, $) {
    'use strict';

    var extractUrlParams = /^([^?]*)\?(.+)$/;
    var parseUrlParam = /^([^=]+)=(.*)$/;

    function updateGraphSizes() {
        $("div.images.monitored-object-detail-view img.graphiteImg").each(function() {
            var e = $(this);
            var src = e.attr("data-actualimageurl");

            if (typeof(src) !== "undefined") {
                var matchParams = extractUrlParams.exec(src);

                if (matchParams !== null) {
                    var urlParams = Object.create(null);

                    matchParams[2].split("&").forEach(function(urlParam) {
                        var matchParam = parseUrlParam.exec(urlParam);
                        if (matchParam !== null) {
                            urlParams[matchParam[1]] = matchParam[2];
                        }
                    });

                    if (typeof(urlParams.width) !== "undefined") {
                        var realWidth = e.width().toString();

                        if (urlParams.width !== realWidth) {
                            urlParams.width = realWidth;

                            var renderedUrlParams = [];

                            for (var urlParam in urlParams) {
                                renderedUrlParams.push(urlParam + "=" + urlParams[urlParam]);
                            }

                            src = matchParams[1] + "?" + renderedUrlParams.join("&");

                            e.attr("data-actualimageurl", src);
                            e.attr("src", src);
                        }
                    }
                }
            }
        });
    }

    function MonitoredObjectDetailViewExtensionUpdater(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', this.onRendered, this);
    }

    MonitoredObjectDetailViewExtensionUpdater.prototype = Object.create(Icinga.EventListener.prototype);

    MonitoredObjectDetailViewExtensionUpdater.prototype.onRendered = function() {
        $(window).on('resize', updateGraphSizes);
        updateGraphSizes();
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.MonitoredObjectDetailViewGraphiteExtensionUpdater = MonitoredObjectDetailViewExtensionUpdater;
}(Icinga, jQuery));
