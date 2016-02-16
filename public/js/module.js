
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

        reloadContainerImgs(idx, imgs)
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

