$(function(){
  const use_cookies = Cookies.get('linear_session_use_cookies');
  if (typeof use_cookies === "undefined") {
    $('html').append('<style>/*! 通知バーのアニメーション用 */@-webkit-keyframes linear-cookie-accept-bar-slide-in{from{-webkit-transform:translateY(-120px);transform:translateY(-120px)}to{-webkit-transform:translateY(0);transform:translateY(0)}}@keyframes linear-cookie-accept-bar-slide-in{from{-webkit-transform:translateY(-120px);transform:translateY(-120px)}to{-webkit-transform:translateY(0);transform:translateY(0)}}@-webkit-keyframes linear-cookie-accept-bar-slide-out{from{-webkit-transform:translateY(0);transform:translateY(0)}to{-webkit-transform:translateY(-120px);transform:translateY(-120px)}}@keyframes linear-cookie-accept-bar-slide-out{from{-webkit-transform:translateY(0);transform:translateY(0)}to{-webkit-transform:translateY(-120px);transform:translateY(-120px)}}/*! 通知バー */.module-linear-cookie-accept-bar{-webkit-animation-duration:.6s;animation-duration:.6s;-webkit-animation-name:linear-cookie-accept-bar-slide-in;animation-name:linear-cookie-accept-bar-slide-in;background-color:#0071b5;-webkit-box-sizing:border-box;box-sizing:border-box;color:#fff;font-size:.875rem;line-height:1.5;padding:5px 20px;position:absolute;left:0;top:0;width:100%;z-index:1000}.module-linear-cookie-accept-bar p{margin:1em 0}.module-linear-cookie-accept-bar a{color:inherit;text-decoration:underline}.module-linear-cookie-accept-bar .material-icons{vertical-align:middle;margin-right:.2em}.module-linear-cookie-accept-bar p button:first-child{margin-right:1em}}</style><div id="name-linear-cookie-accept-bar" class="module-linear-cookie-accept-bar"><p><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> このサイトではログイン情報の保持のために Cookie（クッキー）を使用します。<p><button id="name-linear-cookie-accept-btn" class="btn btn-default btn-sm"><i class="fa fa-check" aria-hidden="true"></i> Cookie を受け入れる</button> <button id="name-linear-cookie-deny-btn" class="btn btn-danger btn-sm"><i class="fa fa-ban" aria-hidden="true"></i> Cookie を受け入れない</button></p></div>');
  }
  $('#name-linear-cookie-accept-btn').on('click', function(){
    Cookies.set('linear_session_use_cookies', true);
    $('#name-linear-cookie-accept-bar .btn').attr('disabled', 'disabled');
    location.reload();
  });
  $('#name-linear-cookie-deny-btn').on('click', function(){
    Cookies.set('linear_session_use_cookies', false);
    $('#name-linear-cookie-accept-bar .btn').attr('disabled', 'disabled');
    location.reload();
  });
});
