_catchy.Clocktower = function() {

  var _sprite,
      _x,
      _y,
      _baseScale;

  var init = function() {
    _scale = 0.46 * _catchy.screen.scaleV;
    _sprite = _catchy.spriteBuilder.getScaledSvgFromSvg(_catchy.screen.gameContainer, 'clocktower', _catchy.screen.scaleV, getDimensions);
    if(_sprite.height > 0) getDimensions();
  };

  var getDimensions = function() {
    _x = _catchy.screen.width / 2;
    _y = _catchy.screen.height - (_sprite.height * _scale) / 2 - (_catchy.screen.scaleV * 60);
  };

  var update = function(xPercent) {
    _x = _catchy.screen.width * 0.76; // + (-0.5 + _catchy.touchControl.percentX.value()) * (_catchy.screen.width * 0.07);
    _sprite.setScale(_scale);
    _sprite.setPosition(_x, _y);
  };

  init();
  return {
    update : update
  };
};
