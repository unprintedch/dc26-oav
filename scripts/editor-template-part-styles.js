(function (wp) {
  if (!wp || !wp.blocks || !wp.data || !wp.hooks) {
    return;
  }

  var blockName = 'core/template-part';
  var styleName = 'sticky-header';
  var styleConfig = {
    name: styleName,
    label: 'Sticky header',
  };

  wp.hooks.addFilter(
    'blocks.registerBlockType',
    'dc26/limit-template-part-styles',
    function (settings, name) {
      if (name !== blockName) {
        return settings;
      }

      var originalGetEditWrapperProps = settings.getEditWrapperProps;

      settings.getEditWrapperProps = function (attributes) {
        var props = originalGetEditWrapperProps
          ? originalGetEditWrapperProps(attributes)
          : {};

        if (!attributes || attributes.area !== 'header') {
          props['data-hide-sticky-style'] = true;
        }

        return props;
      };

      return settings;
    }
  );

  function isStyleRegistered() {
    if (!wp.blocks.getBlockStyles) {
      return true;
    }

    return wp.blocks
      .getBlockStyles(blockName)
      .some(function (style) {
        return style.name === styleName;
      });
  }

  function ensureStyleRegistered() {
    if (isStyleRegistered()) {
      return;
    }

    wp.blocks.registerBlockStyle(blockName, styleConfig);
  }

  function ensureStyleUnregistered() {
    if (!isStyleRegistered()) {
      return;
    }

    wp.blocks.unregisterBlockStyle(blockName, styleName);
  }

  function updateStyleAvailability(selectedBlock) {
    if (!selectedBlock || selectedBlock.name !== blockName) {
      ensureStyleRegistered();
      return;
    }

    if (selectedBlock.attributes && selectedBlock.attributes.area === 'header') {
      ensureStyleRegistered();
      return;
    }

    ensureStyleUnregistered();
  }

  wp.data.subscribe(function () {
    var selectedBlock = wp.data.select('core/block-editor').getSelectedBlock();
    updateStyleAvailability(selectedBlock);
  });
})(window.wp);
