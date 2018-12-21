
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no" />
    <title>骏宝闪充</title>
    <script src="{{ asset('product/assets/js/fastclick.js') }}"></script>
    <script>
        if ('addEventListener' in document) {
            window.addEventListener('load', function() {
                FastClick.attach(document.body);
            }, false);
        }
    </script>
    <script>
        var uid = "{{$device->uid}}";
        var device_id = "{{$device->id}}";
        var price_list = {!! $price_list !!};
        var diff_time = "{{$diff_time}}";
        var password = "{{$password}}";
    </script>
    <script>
        (function(doc, win) {
            var docEl = doc.documentElement,
                resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
                recalc = function() {
                    var clientWidth = docEl.clientWidth;
                    if (!clientWidth) return;
                    if(clientWidth == 375){
                        docEl.style.fontSize = '100px';
                    }else{
                        docEl.style.fontSize = 100 * (clientWidth / 375) + 'px';
                    }
                };
            if (!doc.addEventListener) return;
            win.addEventListener(resizeEvt, recalc, false);
            doc.addEventListener('DOMContentLoaded', recalc, false);
        })(document, window);
    </script>
    <script src="http://res.wx.qq.com/open/js/jweixin-1.2.0.js"></script>
    <script>
        if(!window.Promise) {
            document.writeln('<script src="{{ asset('product/assets/js/es6-promise.min.js') }}"'+'>'+'<'+'/'+'script>');
        }
    </script>
    <link rel="stylesheet" href="{{ asset('product/index.css') }}?v=1.4">
</head>
<body>
<audio id="junbaoaudio" src="http://wx.junbao518.com/product/assets/js/junbao.mp3" autoplay="autoplay" loop="loop"></audio>
<div id="example" />
    <script src="{{ asset('product/assets/js/react-dom.min.js') }}"></script>
    <script src="{{ asset('product/shared.js') }}"></script>
    <script src="{{ asset('product/index.js') }}?v=1.4"></script>
</body>
</html>

