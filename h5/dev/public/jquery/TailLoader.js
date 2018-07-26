define(['jquery'], function ($) {
  /**
   * 尾加载
   * @class TailLoader
   * @author yelo
   * @constructor
   * @param {object} options 与$.ajax参数一致，但其中complete, error, success的上下文为TailLoader实例
   * @param {boolean} isBindBottom 是否自动绑定页尾事件，默认为true
   * @chainable
   * @return {this}
   * @example
   *
   *      var tailLoader = TailLoader({
   *        // 参数与$.ajax一致
   *        url: 'http://foobar.com/api',
   *        data: {
   *          page: 1
   *        },
   *        method: 'get',
   *        beforeSend: function () {
   *          // 显示加载状态提示
   *          LOADER.show();
   *        },
   *        // complete, error, success的上下文(this)为tailLoader
   *        complete: function () {
   *          // 隐藏加载状态提示
   *          LOADER.hide();
   *        },
   *        error: function (xml, status, err) {
   *          // 向用户告知错误信息
   *          ALERT(status);
   *          // 为tailLoader解锁，使其可以继续获取数据
   *          this.unlock();
   *        },
   *        success: function (data) {
   *          // 向DOM加载新数据
   *          CONTAINER.append(RENDER(date));
   *          // 页数加一
   *          this.inc('page');
   *          if (this.data('page') < TOTAL) {
   *            // 还存在下一页时为tailLoader解锁，使其可以继续获取数据
   *            this.unlock();
   *          } else {
   *            // 向用户告知已经是最后一页
   *            ALERT('this is the last page');
   *            // 解绑页尾事件，释放资源
   *            this.unbind();
   *          }
   *        }
   *      });
   */
  var TailLoader = function (options, isBindBottom) {
    var self = this;
    self.options = $.extend({}, options);
    self.options.success = function (data, status, ajax) {
      var success = options.success || $.noop;
      return success.call(self, data, status, ajax);
    };
    self.options.error = function (data, status, ajax) {
      var error = options.error || $.noop;
      return error.call(self, data, status, ajax);
    };
    self.options.complete = function (data, status, ajax) {
      var complete = options.complete || $.noop;
      return complete.call(self, data, status, ajax);
    };
    self.locked = false;
    if (isBindBottom = typeof isBindBottom === 'undefined' ? true : isBindBottom) {
      self.bindBottom();
    }
    return self;
  };
  /**
   * 获取数据(可手动执行)
   * @method fetch
   * @for TailLoader
   * @chainable
   * @return {this}
   */
  TailLoader.prototype.fetch = function () {
    if (this.locked) {
      return this;
    }
    this.locked = true;
    $.ajax(this.options);
    return this;
  };
  /**
   * 获取某项data(发送到服务器的数据)
   * @method data
   * @for TailLoader
   * @param  {string} key   键名
   * @return 对应值
   */
  /**
   * 设置某项data(发送到服务器的数据)
   * @method data
   * @for TailLoader
   * @chainable
   * @param  {string} key   键名
   * @param {object} value 值
   * @return {this}
   */
  TailLoader.prototype.data = function (key, value) {
    if (arguments.length === 1) {
      return this.options.data[key];
    } else {
      this.options.data[key] = value;
      return this;
    }
  };
  /**
   * 使data中某一值自增
   * @method inc
   * @for TailLoader
   * @param  {string} key  键名
   * @param  {number} [step=1] 增量
   * @return 自增后的值
   */
  TailLoader.prototype.inc = function (key, step) {
    return this.options.data[key] += (step = typeof step === 'undefined' ? 1 : step);
  };
  /**
   * 使data中某一值自减
   * @method dec
   * @for TailLoader
   * @param  {string} key  键名
   * @param  {number} [step=1] 减量
   * @return 自减后的值
   */
  TailLoader.prototype.dec = function (key, step) {
    return this.options.data[key] -= (step = typeof step === 'undefined' ? 1 : step);
  };
  /**
   * 解锁
   * @method unlock
   * @for TailLoader
   * @chainable
   * @return {this}
   */
  TailLoader.prototype.unlock = function () {
    var self = this;
    setTimeout(function () {
      self.locked = false;
    }, 0);
    return self;
  };
  /**
   * 手动绑定页尾事件
   * @method bindBottom
   * @for TailLoader
   * @chainable
   * @return {this}
   */
  /**
   * 响应页面滚动事件的方法，通过bindBottom方法自动生成
   * @method onscroll
   * @for TailLoader
   * @private
   */
  TailLoader.prototype.bindBottom = function () {
    var self = this;
    self.onscroll = function() {
      if ($(document).scrollTop() + $(window).height() > $(document).height() - 800) {
        self.fetch();
      }
    };
    $(window).bind("scroll", self.onscroll);
    return self;
  };
  /**
   * 解绑页尾事件
   * @method unbind
   * @for TailLoader
   * @chainable
   * @return {this}
   */
  TailLoader.prototype.unbind = function () {
    $(window).unbind('scroll', this.onscroll);
    return this;
  };

  $.extend({
    /**
     * 尾加载 for jQuery
     * @function $.tailLoader
     * @param  {object} options     
     * @param  {boolean} [bindBottom] 自动绑定尾部事件
     * @return {TailLoader}  loader
     */
    tailLoader: function (options, bindBottom) {
      return new TailLoader(options, bindBottom);
    }
  });

  return TailLoader;
});
