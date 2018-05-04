/* eslint no-alert: 0 */

'use strict';

//
// Here is how to define your module
// has dependent on mobile-angular-ui
//
var app = angular.module('MobileAngularUiExamples', [
  'ngRoute',
  'ngCookies',
  'ngMessages',
  'mobile-angular-ui',
  'angularjs-dropdown-multiselect',

  // touch/drag feature: this is from 'mobile-angular-ui.gestures.js'.
  // This is intended to provide a flexible, integrated and and
  // easy to use alternative to other 3rd party libs like hammer.js, with the
  // final pourpose to integrate gestures into default ui interactions like
  // opening sidebars, turning switches on/off ..
  'mobile-angular-ui.gestures'
]);

app.run(function($transform) {
  window.$transform = $transform;
});

//
// You can configure ngRoute as always, but to take advantage of SharedState location
// feature (i.e. close sidebar on backbutton) you should setup 'reloadOnSearch: false'
// in order to avoid unwanted routing.
//
app.config(function($routeProvider) {
  $routeProvider.when('/', {templateUrl: 'home.html', reloadOnSearch: false});
  $routeProvider.when('/expenses', {templateUrl: 'expenses.html', reloadOnSearch: false});
  $routeProvider.when('/long-term-expenses', {templateUrl: 'long-term-expenses.html', reloadOnSearch: false});
  $routeProvider.when('/incomes', {templateUrl: 'incomes.html', reloadOnSearch: false});
  $routeProvider.when('/debts', {templateUrl: 'debts.html', reloadOnSearch: false});
  $routeProvider.when('/claims', {templateUrl: 'claims.html', reloadOnSearch: false});
  $routeProvider.when('/forms', {templateUrl: 'forms.html', reloadOnSearch: false});
  $routeProvider.when('/login', {templateUrl: 'login.html', reloadOnSearch: false});
});

//
// `$touch example`
//

app.directive('toucharea', ['$touch', function($touch) {
  // Runs during compile
  return {
    restrict: 'C',
    link: function($scope, elem) {
      $scope.touch = null;
      $touch.bind(elem, {
        start: function(touch) {
          $scope.containerRect = elem[0].getBoundingClientRect();
          $scope.touch = touch;
          $scope.$apply();
        },

        cancel: function(touch) {
          $scope.touch = touch;
          $scope.$apply();
        },

        move: function(touch) {
          $scope.touch = touch;
          $scope.$apply();
        },

        end: function(touch) {
          $scope.touch = touch;
          $scope.$apply();
        }
      });
    }
  };
}]);

//
// `$drag` example: drag to dismiss
//
app.directive('dragToDismiss', function($drag, $parse, $timeout) {
  return {
    restrict: 'A',
    compile: function(elem, attrs) {
      var dismissFn = $parse(attrs.dragToDismiss);
      return function(scope, elem) {
        var dismiss = false;

        $drag.bind(elem, {
          transform: $drag.TRANSLATE_RIGHT,
          move: function(drag) {
            if (drag.distanceX >= drag.rect.width / 4) {
              dismiss = true;
              elem.addClass('dismiss');
            } else {
              dismiss = false;
              elem.removeClass('dismiss');
            }
          },
          cancel: function() {
            elem.removeClass('dismiss');
          },
          end: function(drag) {
            if (dismiss) {
              elem.addClass('dismitted');
              $timeout(function() {
                scope.$apply(function() {
                  dismissFn(scope);
                });
              }, 300);
            } else {
              drag.reset();
            }
          }
        });
      };
    }
  };
});

//
// Another `$drag` usage example: this is how you could create
// a touch enabled "deck of cards" carousel. See `carousel.html` for markup.
//
app.directive('carousel', function() {
  return {
    restrict: 'C',
    scope: {},
    controller: function() {
      this.itemCount = 0;
      this.activeItem = null;

      this.addItem = function() {
        var newId = this.itemCount++;
        this.activeItem = this.itemCount === 1 ? newId : this.activeItem;
        return newId;
      };

      this.next = function() {
        this.activeItem = this.activeItem || 0;
        this.activeItem = this.activeItem === this.itemCount - 1 ? 0 : this.activeItem + 1;
      };

      this.prev = function() {
        this.activeItem = this.activeItem || 0;
        this.activeItem = this.activeItem === 0 ? this.itemCount - 1 : this.activeItem - 1;
      };
    }
  };
});

app.directive('carouselItem', function($drag) {
  return {
    restrict: 'C',
    require: '^carousel',
    scope: {},
    transclude: true,
    template: '<div class="item"><div ng-transclude></div></div>',
    link: function(scope, elem, attrs, carousel) {
      scope.carousel = carousel;
      var id = carousel.addItem();

      var zIndex = function() {
        var res = 0;
        if (id === carousel.activeItem) {
          res = 2000;
        } else if (carousel.activeItem < id) {
          res = 2000 - (id - carousel.activeItem);
        } else {
          res = 2000 - (carousel.itemCount - 1 - carousel.activeItem + id);
        }
        return res;
      };

      scope.$watch(function() {
        return carousel.activeItem;
      }, function() {
        elem[0].style.zIndex = zIndex();
      });

      $drag.bind(elem, {
        //
        // This is an example of custom transform function
        //
        transform: function(element, transform, touch) {
          //
          // use translate both as basis for the new transform:
          //
          var t = $drag.TRANSLATE_BOTH(element, transform, touch);

          //
          // Add rotation:
          //
          var Dx = touch.distanceX;
          var t0 = touch.startTransform;
          var sign = Dx < 0 ? -1 : 1;
          var angle = sign * Math.min((Math.abs(Dx) / 700) * 30, 30);

          t.rotateZ = angle + (Math.round(t0.rotateZ));

          return t;
        },
        move: function(drag) {
          if (Math.abs(drag.distanceX) >= drag.rect.width / 4) {
            elem.addClass('dismiss');
          } else {
            elem.removeClass('dismiss');
          }
        },
        cancel: function() {
          elem.removeClass('dismiss');
        },
        end: function(drag) {
          elem.removeClass('dismiss');
          if (Math.abs(drag.distanceX) >= drag.rect.width / 4) {
            scope.$apply(function() {
              carousel.next();
            });
          }
          drag.reset();
        }
      });
    }
  };
});

app.directive('dragMe', ['$drag', function($drag) {
  return {
    controller: function($scope, $element) {
      $drag.bind($element,
        {
          //
          // Here you can see how to limit movement
          // to an element
          //
          transform: $drag.TRANSLATE_INSIDE($element.parent()),
          end: function(drag) {
            // go back to initial position
            drag.reset();
          }
        },
        { // release touch when movement is outside bounduaries
          sensitiveArea: $element.parent()
        }
      );
    }
  };
}]);

//
// For this trivial demo we have just a unique MainController
// for everything
//
app.controller('MainController', ['$rootScope', '$scope', '$cookies','$cookieStore', function($rootScope, $scope, $cookies, $cookieStore) {

  $rootScope.user = {'email': null};
  $rootScope.infoBox = {visible: false, success: true};

  $scope.swiped = function(direction) {
    alert('Swiped ' + direction);
  };

  // User agent displayed in home page
  $scope.userAgent = navigator.userAgent;

  // Needed for the loading screen
  $rootScope.$on('$routeChangeStart', function() {
    $rootScope.loading = true;

    // if user is not looget, redirect him to the login page
    if($cookies.get('logged') != 1){
      window.location = './#/login';
    }
  });

  $rootScope.$on('$routeChangeSuccess', function() {
    $rootScope.loading = false;
  });

  // Fake text i used here and there.
  $scope.lorem = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. ' +
    'Vel explicabo, aliquid eaque soluta nihil eligendi adipisci error, illum ' +
    'corrupti nam fuga omnis quod quaerat mollitia expedita impedit dolores ipsam. Obcaecati.';

  //
  // 'Scroll' screen
  //
  var scrollItems = [];

  for (var i = 1; i <= 100; i++) {
    scrollItems.push('Item ' + i);
  }

  $scope.scrollItems = scrollItems;

  $scope.bottomReached = function() {
    alert('Congrats you scrolled to the end of the list!');
  };

  //
  // Right Sidebar
  //
  $scope.chatUsers = [
    {name: 'Carlos  Flowers', online: true},
    {name: 'Byron Taylor', online: true},
    {name: 'Jana  Terry', online: true},
    {name: 'Darryl  Stone', online: true},
    {name: 'Fannie  Carlson', online: true},
    {name: 'Holly Nguyen', online: true},
    {name: 'Bill  Chavez', online: true},
    {name: 'Veronica  Maxwell', online: true},
    {name: 'Jessica Webster', online: true},
    {name: 'Jackie  Barton', online: true},
    {name: 'Crystal Drake', online: false},
    {name: 'Milton  Dean', online: false},
    {name: 'Joann Johnston', online: false},
    {name: 'Cora  Vaughn', online: false},
    {name: 'Nina  Briggs', online: false},
    {name: 'Casey Turner', online: false},
    {name: 'Jimmie  Wilson', online: false},
    {name: 'Nathaniel Steele', online: false},
    {name: 'Aubrey  Cole', online: false},
    {name: 'Donnie  Summers', online: false},
    {name: 'Kate  Myers', online: false},
    {name: 'Priscilla Hawkins', online: false},
    {name: 'Joe Barker', online: false},
    {name: 'Lee Norman', online: false},
    {name: 'Ebony Rice', online: false}
  ];

  //
  // 'Forms' screen
  //
  $scope.rememberMe = true;

  $scope.login = function() {
    alert('You submitted the login form');
  };

  //
  // 'Drag' screen
  //
  $scope.notices = [];

  for (var j = 0; j < 10; j++) {
    $scope.notices.push({icon: 'envelope', message: 'Notice ' + (j + 1)});
  }

  $scope.deleteNotice = function(notice) {
    var index = $scope.notices.indexOf(notice);
    if (index > -1) {
      $scope.notices.splice(index, 1);
    }
  };
}]);


//added by me
app.controller('loginCtrl', function($rootScope, $scope, $http){
  $scope.login = function(){
      //window.alert("user: " + $scope.email + " " + $scope.password + " " + $scope.rememberMe);
      $http.post('srv/loader.php?requri=login', {'email': $scope.email, 'pass': $scope.pass})
           .then(function successfulLogin(response){
             $rootScope.user = {'email': $scope.email}
             window.location = './#/';

           }, function failedLogin(response){
             console.log('Unauthorized');
             console.log(response);
           });
  };
});

app.controller('addExpenseCtrl', function($rootScope, $scope, $http, $timeout){
  $scope.typesSelected = [];

  $http.get('srv/loader.php?requri=expenses&types')
       .then(function successfullRequest(response){
         $scope.types = response.data;
       },function failedRequest(response){
         console.log('types load failed');
       });

  $scope.addExpense = function(){

    function postExpenseData(data){
      var date = new Date(data.date);
      data.date = date.getTime();

      console.log(data.date);

      $http.post('srv/loader.php?requri=expenses', data)
           .then(function(response){
             //sucess
             $rootScope.infoBox = {visible: true, success: true};
             $timeout(function(){
               $rootScope.infoBox = {visible: false, success: true};
             }, 600);
           },function(response){
             //fail
             $rootScope.infoBox = {visible: true, success: false};
             $timeout(function(){
               $rootScope.infoBox = {visible: false, success: false};
             }, 1200);
           });

       $scope.cost = null;
       $scope.typesSelected = [];
       $scope.description = null;
       $scope.date = null;
       $scope.addExpenseForm.$setPristine();
       $scope.addExpenseForm.$setUntouched();
    }

    if($scope.getLocation){
      if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(function(position){
          postExpenseData({
            'cost': $scope.cost,
            'types': $scope.typesSelected,
            'description': $scope.description,
            'date': $scope.date,
            'location': {
                'latitude': position.coords.latitude,
                'longitude': position.coords.longitude,
                'altitude': position.coords.altitude
            }
          });
        });
      }
    }else{
      postExpenseData({
        'cost': $scope.cost,
        'types': $scope.typesSelected,
        'description': $scope.description,
        'date': $scope.date
      });
    }
  };
});



app.controller('addLongTermExpenseCtrl', function($rootScope, $scope, $http, $timeout){
  $scope.typesSelected = [];

  $http.get('srv/loader.php?requri=long-term-expenses&types')
       .then(function successfullRequest(response){
         $scope.types = response.data;
       },function failedRequest(response){
         console.log('types load failed');
       });

  $scope.addLongTermExpense = function(){

    var date = new Date($scope.date);

    var data = {
      'cost': $scope.cost,
      'types': $scope.typesSelected,
      'description': $scope.description,
      'date': date.getTime()
    }

    $http.post('srv/loader.php?requri=long-term-expenses', data)
         .then(function(response){
           //sucess
           $rootScope.infoBox = {visible: true, success: true};
           $timeout(function(){
             $rootScope.infoBox = {visible: false, success: true};
           }, 600);
         },function(response){
           //fail
           $rootScope.infoBox = {visible: true, success: false};
           $timeout(function(){
             $rootScope.infoBox = {visible: false, success: false};
           }, 1200);
         });

     $scope.cost = null;
     $scope.typesSelected = [];
     $scope.description = null;
     $scope.date = null;
     $scope.addLongTermExpenseForm.$setPristine();
     $scope.addLongTermExpenseForm.$setUntouched();

  };
});




app.controller('addIncomeCtrl', function($rootScope, $scope, $http, $timeout){
  $scope.typesSelected = [];

  $http.get('srv/loader.php?requri=incomes&types')
       .then(function successfullRequest(response){
         $scope.types = response.data;
       },function failedRequest(response){
         console.log('types load failed');
       });

  $scope.addIncome = function(){

    var date = new Date($scope.date);

    var data = {
      'cost': $scope.cost,
      'types': $scope.typesSelected,
      'description': $scope.description,
      'date': date.getTime()
    }

    $http.post('srv/loader.php?requri=incomes', data)
         .then(function(response){
           //sucess
           $rootScope.infoBox = {visible: true, success: true};
           $timeout(function(){
             $rootScope.infoBox = {visible: false, success: true};
           }, 600);
         },function(response){
           //fail
           $rootScope.infoBox = {visible: true, success: false};
           $timeout(function(){
             $rootScope.infoBox = {visible: false, success: false};
           }, 1200);
         });

     $scope.cost = null;
     $scope.typesSelected = [];
     $scope.description = null;
     $scope.date = null;
     $scope.addIncomeForm.$setPristine();
     $scope.addIncomeForm.$setUntouched();

  };
});



app.controller('addDebtCtrl', function($rootScope, $scope, $http, $timeout){
  $scope.typesSelected = [];

  $http.get('srv/loader.php?requri=debts&types')
       .then(function successfullRequest(response){
         $scope.types = response.data;
       },function failedRequest(response){
         console.log('types load failed');
       });

  $scope.addDebt = function(){

    var date = new Date($scope.date);
    var dueDate = new Date($scope.dueDate);

    var data = {
      'cost': $scope.cost,
      'types': $scope.typesSelected,
      'description': $scope.description,
      'date': date.getTime(),
      'dueDate': dueDate.getTime()
    }

    $http.post('srv/loader.php?requri=debts', data)
         .then(function(response){
           //sucess
           $rootScope.infoBox = {visible: true, success: true};
           $timeout(function(){
             $rootScope.infoBox = {visible: false, success: true};
           }, 600);
         },function(response){
           //fail
           $rootScope.infoBox = {visible: true, success: false};
           $timeout(function(){
             $rootScope.infoBox = {visible: false, success: false};
           }, 1200);
         });

     $scope.cost = null;
     $scope.typesSelected = [];
     $scope.description = null;
     $scope.date = null;
     $scope.dueDate = null;
     $scope.addDebtForm.$setPristine();
     $scope.addDebtForm.$setUntouched();

  };
});



app.controller('addClaimCtrl', function($rootScope, $scope, $http, $timeout){
  $scope.typesSelected = [];

  $http.get('srv/loader.php?requri=claims&types')
       .then(function successfullRequest(response){
         $scope.types = response.data;
       },function failedRequest(response){
         console.log('types load failed');
       });

  $scope.checkDate = function(){
    if($scope.date == null){
        var currentDate = new Date();
        var dueDate = new Date($scope.dueDate);

        if(currentDate.getTime() > dueDate.getTime()){
          $scope.addClaimForm.date.$setValidity("dueDateGtCurr", false);
        }else{
          $scope.addClaimForm.date.$setValidity("dueDateGtCurr",true);
        }
    }else if($scope.date > $scope.dueDate){
      $scope.addClaimForm.date.$setValidity("dueDateGtDate", false);
    }else{
      $scope.addClaimForm.date.$setValidity("dueDateGtDate", true);
    }
  }

  $scope.addClaim = function(){

    var date = new Date($scope.date);
    var dueDate = new Date($scope.dueDate);

    var data = {
      'cost': $scope.cost,
      'types': $scope.typesSelected,
      'description': $scope.description,
      'date': (date.getTime() / 1000),
      'dueDate': (dueDate.getTime() / 1000)
    }

    $http.post('srv/loader.php?requri=claims', data)
         .then(function(response){
           //sucess
           $rootScope.infoBox = {visible: true, success: true};
           $timeout(function(){
             $rootScope.infoBox = {visible: false, success: true};
           }, 600);
         },function(response){
           //fail
           $rootScope.infoBox = {visible: true, success: false};
           $timeout(function(){
             $rootScope.infoBox = {visible: false, success: false};
           }, 1200);
         });

     $scope.cost = null;
     $scope.typesSelected = [];
     $scope.description = null;
     $scope.date = null;
     $scope.dueDate = null;
     $scope.addClaimForm.$setPristine();
     $scope.addClaimForm.$setUntouched();

  };
});
