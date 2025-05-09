"use strict";
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

// initialization of Popovers
var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
  return new bootstrap.Popover(popoverTriggerEl);
});

// initialization of Tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl);
});

// initialization of Toasts
document.addEventListener("DOMContentLoaded", function() {
  var toastElList = [].slice.call(document.querySelectorAll(".toast"));

  var toastList = toastElList.map(function(toastEl) {
    return new bootstrap.Toast(toastEl);
  });

  var toastButtonList = [].slice.call(document.querySelectorAll(".toast-btn"));

  toastButtonList.map(function(toastButtonEl) {
    toastButtonEl.addEventListener("click", function() {
      var toastToTrigger = document.getElementById(toastButtonEl.dataset.target);

      if (toastToTrigger) {
        var toast = bootstrap.Toast.getInstance(toastToTrigger);
        toast.show();
      }
    });
  });
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

// Multi Level Dropdown
function dropDown(a) {
  if (!document.querySelector('.dropdown-hover')) {
    event.stopPropagation();
    event.preventDefault();
    var multilevel = a.parentElement.parentElement.children;

    for (var i = 0; i < multilevel.length; i++) {
      if (multilevel[i].lastElementChild != a.parentElement.lastElementChild) {
        multilevel[i].lastElementChild.classList.remove('show');
      }
    }

    if (!a.nextElementSibling.classList.contains("show")) {
      a.nextElementSibling.classList.add("show");
    } else {
      a.nextElementSibling.classList.remove("show");
    }
  }
}

// Fixed Plugin
if (document.querySelector('.fixed-plugin')) {
  var fixedPlugin = document.querySelector('.fixed-plugin');
  var fixedPluginButton = document.querySelector('.fixed-plugin-button');
  var fixedPluginButtonNav = document.querySelector('.fixed-plugin-button-nav');
  var fixedPluginCard = document.querySelector('.fixed-plugin .card');
  var fixedPluginCloseButton = document.querySelectorAll('.fixed-plugin-close-button');
  var navbar = document.getElementById('navbarBlur');
  var buttonNavbarFixed = document.getElementById('navbarFixed');

  if (fixedPluginButton) {
    fixedPluginButton.onclick = function() {
      if (!fixedPlugin.classList.contains('show')) {
        fixedPlugin.classList.add('show');
      } else {
        fixedPlugin.classList.remove('show');
      }
    };
  }

  if (fixedPluginButtonNav) {
    fixedPluginButtonNav.onclick = function() {
      if (!fixedPlugin.classList.contains('show')) {
        fixedPlugin.classList.add('show');
      } else {
        fixedPlugin.classList.remove('show');
      }
    };
  }

  fixedPluginCloseButton.forEach(function(el) {
    el.onclick = function() {
      fixedPlugin.classList.remove('show');
    };
  });

  document.querySelector('body').onclick = function(e) {
    if (e.target != fixedPluginButton && e.target != fixedPluginButtonNav && e.target.closest('.fixed-plugin .card') != fixedPluginCard) {
      fixedPlugin.classList.remove('show');
    }
  };

  if (navbar) {
    if (navbar.getAttribute('data-scroll') == 'true' && buttonNavbarFixed) {
      buttonNavbarFixed.setAttribute("checked", "true");
    }
  }

}

// Set Navbar Fixed
function navbarFixed(el) {
  var classes = ['position-sticky', 'blur', 'shadow-blur', 'mt-4', 'left-auto', 'top-1', 'z-index-sticky'];
  const navbar = document.getElementById('navbarBlur');

  if (!el.getAttribute("checked")) {
    navbar.classList.add(...classes);
    navbar.setAttribute('data-scroll', 'true');
    navbarBlurOnScroll('navbarBlur');
    el.setAttribute("checked", "true");
  } else {
    navbar.classList.remove(...classes);
    navbar.setAttribute('data-scroll', 'false');
    navbarBlurOnScroll('navbarBlur');
    el.removeAttribute("checked");
  }
}

// Set Navbar Minimized
function navbarMinimize(el) {
  var sidenavShow = document.getElementsByClassName('g-sidenav-show')[0];

  if (!el.getAttribute("checked")) {
    sidenavShow.classList.remove('g-sidenav-pinned');
    sidenavShow.classList.add('g-sidenav-hidden');
    el.setAttribute("checked", "true");
  } else {
    sidenavShow.classList.remove('g-sidenav-hidden');
    sidenavShow.classList.add('g-sidenav-pinned');
    el.removeAttribute("checked");
  }
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

// Tabs navigation

var total = document.querySelectorAll('.nav-pills');

function initNavs() {
  total.forEach(function(item, i) {
    var moving_div = document.createElement('div');
    var li_active = item.querySelector(".nav-link.active");
    var tab = li_active.cloneNode();
    tab.innerHTML = "-";

    moving_div.classList.add('moving-tab', 'position-absolute', 'nav-link');
    moving_div.appendChild(tab);
    item.appendChild(moving_div);

    var list_length = item.getElementsByTagName("li").length;

    moving_div.style.padding = '0px';
    moving_div.style.transition = '.5s ease';

    var li = item.querySelector(".nav-link.active").parentElement;

    if (li) {
      var nodes = Array.from(li.closest('ul').children); // get array
      var index = nodes.indexOf(li) + 1;

      var sum = 0;
      if (item.classList.contains('flex-column')) {
        for (var j = 1; j <= nodes.indexOf(li); j++) {
          sum += item.querySelector('li:nth-child(' + j + ')').offsetHeight;
        }
        moving_div.style.transform = 'translate3d(0px,' + sum + 'px, 0px)';
        moving_div.style.width = item.querySelector('li:nth-child(' + index + ')').offsetWidth + 'px';
        moving_div.style.height = item.querySelector('li:nth-child(' + j + ')').offsetHeight;
      } else {
        for (var j = 1; j <= nodes.indexOf(li); j++) {
          sum += item.querySelector('li:nth-child(' + j + ')').offsetWidth;
        }
        moving_div.style.transform = 'translate3d(' + sum + 'px, 0px, 0px)';
        moving_div.style.width = item.querySelector('li:nth-child(' + index + ')').offsetWidth + 'px';

      }
    }

    item.onmouseover = function(event) {
      var target = getEventTarget(event);
      var li = target.closest('li'); // get reference
      if (li) {
        var nodes = Array.from(li.closest('ul').children); // get array
        var index = nodes.indexOf(li) + 1;
        item.querySelector('li:nth-child(' + index + ') .nav-link').onclick = function() {
          moving_div = item.querySelector('.moving-tab');
          var sum = 0;
          if (item.classList.contains('flex-column')) {
            for (var j = 1; j <= nodes.indexOf(li); j++) {
              sum += item.querySelector('li:nth-child(' + j + ')').offsetHeight;
            }
            moving_div.style.transform = 'translate3d(0px,' + sum + 'px, 0px)';
            moving_div.style.height = item.querySelector('li:nth-child(' + j + ')').offsetHeight;
          } else {
            for (var j = 1; j <= nodes.indexOf(li); j++) {
              sum += item.querySelector('li:nth-child(' + j + ')').offsetWidth;
            }
            moving_div.style.transform = 'translate3d(' + sum + 'px, 0px, 0px)';
            moving_div.style.width = item.querySelector('li:nth-child(' + index + ')').offsetWidth + 'px';
          }
        };
      }
    };
  });
}

setTimeout(function() {
  initNavs();
}, 100);

// Tabs navigation resize

window.addEventListener('resize', function(event) {
  total.forEach(function(item, i) {
    item.querySelector('.moving-tab').remove();
    var moving_div = document.createElement('div');
    var tab = item.querySelector(".nav-link.active").cloneNode();
    tab.innerHTML = "-";

    moving_div.classList.add('moving-tab', 'position-absolute', 'nav-link');
    moving_div.appendChild(tab);

    item.appendChild(moving_div);

    moving_div.style.padding = '0px';
    moving_div.style.transition = '.5s ease';

    var li = item.querySelector(".nav-link.active").parentElement;

    if (li) {
      var nodes = Array.from(li.closest('ul').children); // get array
      var index = nodes.indexOf(li) + 1;

      var sum = 0;
      if (item.classList.contains('flex-column')) {
        for (var j = 1; j <= nodes.indexOf(li); j++) {
          sum += item.querySelector('li:nth-child(' + j + ')').offsetHeight;
        }
        moving_div.style.transform = 'translate3d(0px,' + sum + 'px, 0px)';
        moving_div.style.width = item.querySelector('li:nth-child(' + index + ')').offsetWidth + 'px';
        moving_div.style.height = item.querySelector('li:nth-child(' + j + ')').offsetHeight;
      } else {
        for (var j = 1; j <= nodes.indexOf(li); j++) {
          sum += item.querySelector('li:nth-child(' + j + ')').offsetWidth;
        }
        moving_div.style.transform = 'translate3d(' + sum + 'px, 0px, 0px)';
        moving_div.style.width = item.querySelector('li:nth-child(' + index + ')').offsetWidth + 'px';

      }
    }
  });

  if (window.innerWidth < 991) {
    total.forEach(function(item, i) {
      if (!item.classList.contains('flex-column')) {
        item.classList.remove('flex-row');
        item.classList.add('flex-column', 'on-resize');
        var li = item.querySelector(".nav-link.active").parentElement;
        var nodes = Array.from(li.closest('ul').children); // get array
        var index = nodes.indexOf(li) + 1;
        var sum = 0;
        for (var j = 1; j <= nodes.indexOf(li); j++) {
          sum += item.querySelector('li:nth-child(' + j + ')').offsetHeight;
        }
        var moving_div = document.querySelector('.moving-tab');
        moving_div.style.width = item.querySelector('li:nth-child(1)').offsetWidth + 'px';
        moving_div.style.transform = 'translate3d(0px,' + sum + 'px, 0px)';

      }
    });
  } else {
    total.forEach(function(item, i) {
      if (item.classList.contains('on-resize')) {
        item.classList.remove('flex-column', 'on-resize');
        item.classList.add('flex-row');
        var li = item.querySelector(".nav-link.active").parentElement;
        var nodes = Array.from(li.closest('ul').children); // get array
        var index = nodes.indexOf(li) + 1;
        var sum = 0;
        for (var j = 1; j <= nodes.indexOf(li); j++) {
          sum += item.querySelector('li:nth-child(' + j + ')').offsetWidth;
        }
        var moving_div = document.querySelector('.moving-tab');
        moving_div.style.transform = 'translate3d(' + sum + 'px, 0px, 0px)';
        moving_div.style.width = item.querySelector('li:nth-child(' + index + ')').offsetWidth + 'px';
      }
    });
  }
});

// Function to remove flex row on mobile devices
if (window.innerWidth < 991) {
  total.forEach(function(item, i) {
    if (item.classList.contains('flex-row')) {
      item.classList.remove('flex-row');
      item.classList.add('flex-column', 'on-resize');
    }
  });
}

function getEventTarget(e) {
  e = e || window.event;
  return e.target || e.srcElement;
}

// End tabs navigation

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

// Notification function
function notify(el) {
  var body = document.querySelector('body');
  var alert = document.createElement('div');
  alert.classList.add('alert', 'position-absolute', 'top-0', 'border-0', 'text-white', 'w-50', 'end-0', 'start-0', 'mt-2', 'mx-auto', 'py-2');
  alert.classList.add('alert-' + el.getAttribute('data-type'));
  alert.style.transform = 'translate3d(0px, 0px, 0px)';
  alert.style.opacity = '0';
  alert.style.transition = '.35s ease';
  alert.style.zIndex = '9999';
  setTimeout(function() {
    alert.style.transform = 'translate3d(0px, 20px, 0px)';
    alert.style.setProperty("opacity", "1", "important");
  }, 100);

  alert.innerHTML = '<div class="d-flex mb-1">' +
    '<div class="alert-icon me-1">' +
    '<i class="' + el.getAttribute('data-icon') + ' mt-1"></i>' +
    '</div>' +
    '<span class="alert-text"><strong>' + el.getAttribute('data-title') + '</strong></span>' +
    '</div>' +
    '<span class="text-sm">' + el.getAttribute('data-content') + '</span>';

  body.appendChild(alert);
  setTimeout(function() {
    alert.style.transform = 'translate3d(0px, 0px, 0px)';
    alert.style.setProperty("opacity", "0", "important");
  }, 4000);
  setTimeout(function() {
    el.parentElement.querySelector('.alert').remove();
  }, 4500);
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

//Show input error messages
function showError(input, message) {
  const formControl = input.parentElement;
  formControl.className = 'input-group input-group-outline my-5 is-invalid is-filled';
  const small = formControl.querySelector('small');
  small.innerText = message;
}

//show success colour
function showSucces(input) {
  const formControl = input.parentElement;
  formControl.className = 'input-group input-group-outline my-5 is-valid is-filled';
}

//check email is valid
function checkEmail(input) {
  const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  if (re.test(input.value.trim())) {
    showSucces(input);
  } else {
    showError(input, 'Email is not invalid');
  }
}

//checkRequired fields
function checkRequired(inputArr) {
  inputArr.forEach(function(input) {
    if (input.value.trim() === '') {
      showError(input, `${getFieldName(input)} is required`);
    } else {
      showSucces(input);
    }
  });
}


//check input Length
function checkLength(input, min, max) {
  if (input.value.length < min) {
    showError(input, `${getFieldName(input)} must be at least ${min} characters`);
  } else if (input.value.length > max) {
    showError(input, `${getFieldName(input)} must be les than ${max} characters`);
  } else {
    showSucces(input);
  }
}

//get FieldName
function getFieldName(input) {
  return input.id.charAt(0).toUpperCase() + input.id.slice(1);
}

// check passwords match
function checkPasswordMatch(input1, input2) {
  if (input1.value !== input2.value) {
    showError(input2, 'Passwords do not match');
  }
}

// adding on inputs attributes for calling the focused and defocused functions
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
