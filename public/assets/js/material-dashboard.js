"use strict";

function observeAi(selector, init) {
  new MutationObserver(function() {
    document.querySelectorAll(selector).forEach(init);
  }).observe(document, {
    childList: true,
    subtree: true
  });
}

(function() {
  var isWindows = navigator.platform.indexOf('Win') > -1 ? true : false;

  if (isWindows) {
    // if we are on windows OS we activate the perfectScrollbar function
    if (document.getElementsByClassName('main-content')[0]) {
      var mainpanel = document.querySelector('.main-content');
      var ps = new PerfectScrollbar(mainpanel);
    }

    if (document.getElementsByClassName('sidenav')[0]) {
      var sidebar = document.querySelector('.sidenav');
      var ps1 = new PerfectScrollbar(sidebar);
    }

    if (document.getElementsByClassName('navbar-collapse')[0]) {
      var fixedplugin1 = document.querySelector('.navbar:not(.navbar-expand-lg) .navbar-collapse');
      var ps2 = new PerfectScrollbar(fixedplugin1);
    }

    if (document.getElementsByClassName('fixed-plugin')[0]) {
      var fixedplugin2 = document.querySelector('.fixed-plugin');
      var ps3 = new PerfectScrollbar(fixedplugin2);
    }
  }
})();

// Verify navbar blur on scroll
if (document.getElementById('navbarBlur')) {
  navbarBlurOnScroll('navbarBlur');
}

// initialization of Tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl);
});

// when input is focused add focused class for style
function focused(el) {
  if (el.parentElement.classList.contains('input-group')) {
    el.parentElement.classList.add('focused');
  }
}

// when input is focused remove focused class for style
function defocused(el) {
  if (el.parentElement.classList.contains('input-group')) {
    el.parentElement.classList.remove('focused');
  }
}

// helper for adding on all elements multiple attributes
function setAttributes(el, options) {
  Object.keys(options).forEach(function(attr) {
    el.setAttribute(attr, options[attr]);
  });
}

// adding on inputs attributes for calling the focused and defocused functions
if (document.querySelectorAll('.input-group').length != 0) {
  var allInputs = document.querySelectorAll('input.form-control');
  allInputs.forEach(el => setAttributes(el, {
    "onfocus": "focused(this)",
    "onfocusout": "defocused(this)"
  }));
}

// Navbar blur on scroll
function navbarBlurOnScroll(id) {
  const navbar = document.getElementById(id);
  var navbarScrollActive = navbar ? navbar.getAttribute("data-scroll") : false;
  var scrollDistance = 5;
  var classes = ['blur', 'shadow-blur', 'left-auto'];
  var toggleClasses = ['shadow-none'];

  if (navbarScrollActive == 'true') {
    window.onscroll = debounce(function() {
      if (window.scrollY > scrollDistance) {
        blurNavbar();
      } else {
        transparentNavbar();
      }
    }, 10);
  } else {
    window.onscroll = debounce(function() {
      transparentNavbar();
    }, 10);
  }

  var isWindows = navigator.platform.indexOf('Win') > -1 ? true : false;

  if (isWindows) {
    var content = document.querySelector('.main-content');
    if (navbarScrollActive == 'true') {
      content.addEventListener('ps-scroll-y', debounce(function() {
        if (content.scrollTop > scrollDistance) {
          blurNavbar();
        } else {
          transparentNavbar();
        }
      }, 10));
    } else {
      content.addEventListener('ps-scroll-y', debounce(function() {
        transparentNavbar();
      }, 10));
    }
  }

  function blurNavbar() {
    navbar.classList.add(...classes);
    navbar.classList.remove(...toggleClasses);

    toggleNavLinksColor('blur');
  }

  function transparentNavbar() {
    navbar.classList.remove(...classes);
    navbar.classList.add(...toggleClasses);

    toggleNavLinksColor('transparent');
  }

  function toggleNavLinksColor(type) {
    var navLinks = document.querySelectorAll('.navbar-main .nav-link');
    var navLinksToggler = document.querySelectorAll('.navbar-main .sidenav-toggler-line');

    if (type === "blur") {
      navLinks.forEach(element => {
        element.classList.remove('text-body');
      });

      navLinksToggler.forEach(element => {
        element.classList.add('bg-dark');
      });
    } else if (type === "transparent") {
      navLinks.forEach(element => {
        element.classList.add('text-body');
      });

      navLinksToggler.forEach(element => {
        element.classList.remove('bg-dark');
      });
    }
  }
}

// Debounce Function
// Returns a function, that, as long as it continues to be invoked, will not
// be triggered. The function will be called after it stops being called for
// N milliseconds. If `immediate` is passed, trigger the function on the
// leading edge, instead of the trailing.
function debounce(func, wait, immediate) {
  var timeout;
  return function() {
    var context = this,
      args = arguments;
    var later = function() {
      timeout = null;
      if (!immediate) func.apply(context, args);
    };
    var callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
    if (callNow) func.apply(context, args);
  };
}

// click to minimize the sidebar or reverse to normal
if (document.querySelector('.sidenav-toggler')) {
  var sidenavToggler = document.getElementsByClassName('sidenav-toggler')[0];
  var sidenavShow = document.getElementsByClassName('g-sidenav-show')[0];
  var toggleNavbarMinimize = document.getElementById('navbarMinimize');

  if (sidenavShow) {
    sidenavToggler.onclick = function() {
      if (!sidenavShow.classList.contains('g-sidenav-hidden')) {
        sidenavShow.classList.add('g-sidenav-pinned');
        sidenavShow.classList.add('g-sidenav-hidden');
        if (toggleNavbarMinimize) {
          toggleNavbarMinimize.click();
          toggleNavbarMinimize.setAttribute("checked", "true");
        }
      } else {
        sidenavShow.classList.remove('g-sidenav-hidden');
        sidenavShow.classList.remove('g-sidenav-pinned');
        if (toggleNavbarMinimize) {
          toggleNavbarMinimize.click();
          toggleNavbarMinimize.removeAttribute("checked");
        }
      }
    };
  }
}


// Toggle Sidenav
const iconSidenav = document.getElementById('iconSidenav');
const sidenav = document.getElementById('sidenav-main');
var body = document.getElementsByTagName('body')[0];
var className = 'g-sidenav-pinned';

if (iconSidenav) {
  iconSidenav.addEventListener("click", toggleSidenav);
}

function toggleSidenav() {
  if (body.classList.contains(className)) {
    body.classList.remove(className);
    setTimeout(function() {
      sidenav.classList.remove('bg-white');
    }, 100);
    sidenav.classList.remove('bg-transparent');

  } else {
    body.classList.add(className);
    sidenav.classList.remove('bg-transparent');
    iconSidenav.classList.remove('d-none');
  }
}

window.onload = function() {
  // Material Design Input function
  var inputs = document.querySelectorAll('input, textarea, select');

  for (var i = 0; i < inputs.length; i++) {
    setFilled(inputs[i]);
  }
};

// Material Design Input function
function setFilled(input) {
  if (typeof input.value !== 'undefined' && input.value.length) {
    input.parentElement.classList.add('is-filled');
  }
  input.addEventListener('focus', function(e) {
    this.parentElement.classList.add('is-focused');
  }, false);

  input.addEventListener('input', function(e) {
    if (this.value != "") {
      this.parentElement.classList.add('is-filled');
    } else {
      this.parentElement.classList.remove('is-filled');
    }
  }, false);

  input.addEventListener('focusout', function(e) {
    if (this.value != "") {
      this.parentElement.classList.add('is-filled');
    }
    this.parentElement.classList.remove('is-focused');
  }, false);
}

if (document.querySelectorAll('thead input[type=checkbox]').length != 0) {
  document.querySelectorAll('thead input[type=checkbox]').forEach(function(item, i) {
    item.addEventListener("click", function(){
      var checked = this.checked;
      this.closest('table').querySelectorAll('input[type=checkbox]').forEach(function(item, i) {
        item.checked= checked;
      });
    });
  });
}

document.addEventListener("DOMContentLoaded", function(){
    if (document.querySelectorAll("form[role=search] button.clear").length != 0) {
        document.querySelector("form[role=search] button.clear").addEventListener("click", function(){
            document.querySelector("form[role=search]").querySelectorAll("input[type=text], select").forEach(function(item, i) {
                item.value = '';
            });
            document.querySelector("form[role=search]").submit();
        });
    }

    if (document.querySelectorAll("details[role=search]").length != 0) {
        var closed = true;
        var details = document.querySelector("details[role=search]");
        details.querySelectorAll("input[type=text], select").forEach(function(inputItem, i) {
            if (inputItem.value.length) { closed = false; }
        });
        if (!closed) {
            details.setAttribute("open", true);
            document.querySelectorAll(".summary-search").length != 0 && document.querySelector(".summary-search").remove();
        }
    }

    if (document.querySelectorAll(".summary-search").length != 0) {
        document.querySelector(".summary-search").addEventListener("click", function(e){
            document.querySelector("details[role=search] summary").click();
            bootstrap.Tooltip.getInstance(".summary-search i").dispose();
            this.remove();
        });
    }

    if (document.querySelectorAll("form[role=search] select").length != 0) {
        document.querySelectorAll("form[role=search] select").forEach(function(item, i) {
            item.addEventListener("change", function(){
                document.querySelector("form[role=search]").submit();
            });
        });
    }
});
