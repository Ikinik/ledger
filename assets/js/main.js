/* eslint no-alert: 0 */
'use strict';

//prototypes
Date.prototype.toYMD = function Date_toYMD() {
    var year, month, day;
    year = String(this.getFullYear());
    month = String(this.getMonth() + 1);
    if (month.length == 1) {
        month = "0" + month;
    }
    day = String(this.getDate());
    if (day.length == 1) {
        day = "0" + day;
    }
    return year + "-" + month + "-" + day;
};

//
// Here is how to define your module
// has dependent on mobile-angular-ui
//
var app = angular.module('Ledger', [
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
  $routeProvider.when('/', {templateUrl: 'expenses.html', reloadOnSearch: false});
  $routeProvider.when('/long-term-expenses', {templateUrl: 'long-term-expenses.html', reloadOnSearch: false});
  $routeProvider.when('/incomes', {templateUrl: 'incomes.html', reloadOnSearch: false});
  $routeProvider.when('/debts', {templateUrl: 'debts.html', reloadOnSearch: false});
  $routeProvider.when('/claims', {templateUrl: 'claims.html', reloadOnSearch: false});
  $routeProvider.when('/config', {templateUrl: 'config.html', reloadOnSearch: false});
  $routeProvider.when('/login', {templateUrl: 'login.html', reloadOnSearch: false});
  $routeProvider.when('/register', {templateUrl: 'register.html', reloadOnSearch: false});
});


//FILTERS

app.filter('parseDate', function() {
    return function(dateString) {
        return (dateString == null)? null : Date.parse(dateString);
    };
});

app.filter('parseDateLimitForm', function(){
  return function(dateString){
    if(dateString){
        var selDate = new Date(dateString);
        return selDate.toYMD();
    }else{
        return dateString;
    }
  };
});

app.filter('joinTypes', function() {
    return function(types) {
        var concat = "";

        if(types instanceof Array){
            var length = types.length;
            for(var i = 0; i < length ; i++) {

              if(i != (length - 1)){
                concat += types[i].name + ", ";
              }else{
                concat += types[i].name;
              }
            }
        }

        return concat;
    };
});

//DIRECTIVES

app.directive('googleSignInButton',function($http, $rootScope, $cookies, $timeout){
    return {
        scope:{
            gClientId:'@',
            callback: '&onSignIn'
        },
        template: '<div class="btn btn-default" ng-click="onSignInButtonClick()"><span class="fa fa-google"></span> Sign in</div>',
        controller: ['$scope','$attrs',function($scope, $attrs){
            gapi.load('auth2', function() {//load in the auth2 api's, without it gapi.auth2 will be undefined
                gapi.auth2.init(
                        {
                            client_id: $attrs.gClientId
                        }
                );
                var GoogleAuth  = gapi.auth2.getAuthInstance();//get's a GoogleAuth instance with your client-id, needs to be called after gapi.auth2.init
                $scope.onSignInButtonClick=function(){//add a function to the controller so ng-click can bind to it
                    GoogleAuth.signIn().then(function(response){//request to sign in
                        $scope.callback({response:response});

                        var profile = response.getBasicProfile();
                        var id_token = response.getAuthResponse().id_token;

                        $http.post('srv/loader.php?requri=login/goauth', {'AuthID': id_token})
                             .then(function successfulLogin(response){
                               $rootScope.user = {'email': profile.getEmail()};
                               $cookies.put('email', profile.getEmail());
                               window.location = './#/';

                             }, function failedLogin(response){
                               $rootScope.infoBox = {visible: true, success: false};
                               $timeout(function(){
                                 $rootScope.infoBox = {visible: false, success: false};
                               }, 1800);
                             });

                    });
                };
            });
        }]
    };
});

//
// For this app there is just one unique MainController
// for everything
//
app.controller('MainController', ['$rootScope', '$scope', '$cookies','$cookieStore', function($rootScope, $scope, $cookies, $cookieStore) {

  $rootScope.user = {'email': $cookies.get('email')};
  $rootScope.infoBox = {visible: false, success: true};

  // Needed for the loading screen
  $rootScope.$on('$routeChangeStart', function() {
    $rootScope.loading = true;

    // if user is not looged, redirect him to the login page
    if($cookies.get('logged') != 1 &&
      !(window.location.hash == '#/login' || window.location.hash == '#/register')
    ){
      window.location = './#/login';
      console.log("redirected");
    }
  });

  $rootScope.$on('$routeChangeSuccess', function() {
    $rootScope.loading = false;
  });

  //
  // 'Forms' screen
  //
  $scope.rememberMe = true;

  $scope.logout = function() {
    if(confirm("Do you want to really logout ?")){
        $cookies.remove("PHPSESSID");
        $cookies.remove("logged");
        $cookies.remove("email");

        if($cookies.get("loggedGoogle") == 1){
          $cookies.remove("loggedGoogle");
          var auth2 = gapi.auth2.getAuthInstance();
          auth2.signOut().then(function () {
            console.log('User signed out.');
          });
        }

        window.location = './#/login';
    }
  };

}]);


//controllers
app.controller('loginCtrl', function($rootScope, $scope, $http, $timeout, $cookies, $cookieStore){
  $scope.login = function(){
      //window.alert("user: " + $scope.email + " " + $scope.password + " " + $scope.rememberMe);
      $http.post('srv/loader.php?requri=login', {'email': $scope.email, 'pass': $scope.pass})
           .then(function successfulLogin(response){
             $rootScope.user = {'email': $scope.email};
             $cookies.put('email', $scope.email);
             window.location = './#/';

           }, function failedLogin(response){
             $rootScope.infoBox = {visible: true, success: false};
             $timeout(function(){
               $rootScope.infoBox = {visible: false, success: false};
             }, 1800);
           });
  };
});

app.controller('registerCtrl', function($rootScope, $scope, $http, $timeout){
  $scope.login = function(){
      //window.alert("user: " + $scope.email + " " + $scope.password + " " + $scope.rememberMe);
      $http.post('srv/loader.php?requri=login/register', {'email': $scope.email, 'pass': $scope.pass, 'invitation': $scope.invitation})
           .then(function successfulLogin(response){
             $rootScope.user = {'email': $scope.email};
             $cookies.put('email', $scope.email);
             window.location = './#/';
           }, function failedLogin(response){
             console.log(response);
             $rootScope.infoBox = {visible: true, success: false, message: response.data.message};
             $timeout(function(){
               $rootScope.infoBox = {visible: false, success: false};
             }, 1800);
           });
  };
});

app.controller('addExpenseCtrl', function($rootScope, $scope, $http, $timeout){
  $scope.typesSelected = [];

  $http.get('srv/loader.php?requri=expenses/types')
       .then(function successfullRequest(response){
         $scope.types = response.data;
         //console.log(response.data);
       },function failedRequest(response){
         //console.log('types load failed');
       });

  $scope.addExpense = function(){

    function postExpenseData(data){

      data.date = (data.date)? new Date(data.date).toYMD() : new Date().toYMD();

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
            'date': ($scope.date),
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
        'date': ($scope.date)
      });
    }
  };
});

app.controller('addLongTermExpenseCtrl', function($rootScope, $scope, $http, $timeout){
  $scope.typesSelected = [];

  $http.get('srv/loader.php?requri=long-term-expenses/types')
       .then(function successfullRequest(response){
         $scope.types = response.data;
       },function failedRequest(response){
         //console.log('types load failed');
       });

  $scope.addLongTermExpense = function(){

    //var date = new Date($scope.date);
    var date = ($scope.date)? new Date($scope.date).toYMD() : new Date().toYMD();
    console.log(date);

    var data = {
      'cost': $scope.cost,
      'types': $scope.typesSelected,
      'description': $scope.description,
      'date': date
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

  $http.get('srv/loader.php?requri=incomes/types')
       .then(function successfullRequest(response){
         $scope.types = response.data;
       },function failedRequest(response){
         //console.log('types load failed');
       });

  $scope.addIncome = function(){

    //var date = new Date($scope.date);
    var date = ($scope.date)? new Date($scope.date).toYMD() : new Date().toYMD();

    var data = {
      'cost': $scope.cost,
      'types': $scope.typesSelected,
      'description': $scope.description,
      'date': date
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

  $http.get('srv/loader.php?requri=debts/types')
       .then(function successfullRequest(response){
         $scope.types = response.data;
       },function failedRequest(response){
         //console.log('types load failed');
       });

   $scope.checkDate = function(){
     if($scope.date == null){
         var currentDate = new Date();
         var dueDate = new Date($scope.dueDate);

         if(currentDate.getTime() > dueDate.getTime()){
           $scope.addDebtForm.dueDate.$setValidity("dueDateLtCurr", false);
         }else{
           $scope.addDebtForm.dueDate.$setValidity("dueDateLtCurr",true);
         }
     }else if($scope.date > $scope.dueDate){
       $scope.addDebtForm.dueDate.$setValidity("dueDateLtDate", false);
     }else{
       $scope.addDebtForm.dueDate.$setValidity("dueDateLtDate", true);
     }
   }

  $scope.addDebt = function(){

    var date = ($scope.date)? new Date($scope.date).toYMD() : new Date().toYMD();
    var dueDate = ($scope.dueDate)? new Date($scope.dueDate).toYMD() : null;

    var data = {
      'cost': $scope.cost,
      'types': $scope.typesSelected,
      'description': $scope.description,
      'date': date,
      'dueDate': dueDate
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

  $http.get('srv/loader.php?requri=claims/types')
       .then(function successfullRequest(response){
         $scope.types = response.data;
       },function failedRequest(response){
         //console.log('types load failed');
       });

  $scope.checkDate = function(){
    if($scope.date == null){
        var currentDate = new Date();
        var dueDate = new Date($scope.dueDate);

        if(currentDate.getTime() > dueDate.getTime()){
          $scope.addClaimForm.dueDate.$setValidity("dueDateLtCurr", false);
        }else{
          $scope.addClaimForm.dueDate.$setValidity("dueDateLtCurr",true);
        }
    }else if($scope.date > $scope.dueDate){
      $scope.addClaimForm.dueDate.$setValidity("dueDateLtDate", false);
    }else{
      $scope.addClaimForm.dueDate.$setValidity("dueDateLtDate", true);
    }
  }

  $scope.addClaim = function(){

    var date = ($scope.date)? new Date($scope.date).toYMD() : new Date().toYMD();
    var dueDate = ($scope.dueDate)? new Date($scope.dueDate).toYMD() : null;

    var data = {
      'cost': $scope.cost,
      'types': $scope.typesSelected,
      'description': $scope.description,
      'date': date,
      'dueDate': dueDate
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


app.controller('viewExpensesCtrl', function($rootScope, $scope, $http){
  $scope.typesSelected = [];
  $scope.records = [];
  $scope.recordsReceived = [];
  $scope.order = "date";
  $scope.reverse = true;
  $scope.dateTo = null;
  $scope.dateFrom = null;
  $scope.selectedRow = null;
  $scope.modalVisible = false;

  $scope.typesEvents = {onSelectionChanged: function(){
    var filtered = [];
    var selLength = $scope.typesSelected.length;

    $scope.recordsReceived.forEach(function(value, index, object){
      if(value.types instanceof Array){
        var valueTypesLength = value.types.length;
        var matched = 0;

        //loop throught types in result rows
        for(var c=0; c < valueTypesLength; c++){

            //loop throught selected types
            for(var i=0; i < selLength; i++){
              if($scope.typesSelected[i].id == value.types[c].id){
                matched++;
              }
            }
        }

        //chceck if everithig was matched
        if(matched == selLength){
          filtered.push(value);
        }
      }else if(selLength == 0){
        filtered.push(value);
      }

      $scope.records = filtered;
    });
  }};

  $scope.showDetails = function(record){
    $scope.selectedRow = record;
    $scope.modalVisible = true;
  }

  $scope.recordDelete = function(){
    if($scope.selectedRow.id){
      $http.post('srv/loader.php?requri=expenses/delete', {'id': $scope.selectedRow.id})
           .then(function successfullRequest(response){
             $scope.loadResults();
             $scope.modalVisible = false;
           }, function failedRequest(response){
             //console.log('delete failed');
           });
    }
  }

  $scope.loadResults = function(){

    var dateFrom = new Date($scope.dateFrom);
    var dateTo = new Date($scope.dateTo);

    if(!$scope.dateFrom && !$scope.dateTo){
      //date is not set
      $http.get('srv/loader.php?requri=expenses/view')
           .then(function successfullRequest(response){
             $scope.recordsReceived = response.data;
             $scope.records = response.data;

             $scope.typesEvents.onSelectionChanged();
           },function failedRequest(response){
             //console.log('view data load failed');
           });
    }else if(dateFrom > dateTo){
      $scope.sortExpenseForm.dateFrom.$setValidity("dateToGrDateFrom", false);
    }else{
      $scope.sortExpenseForm.dateFrom.$setValidity("dateToGrDateFrom", true);

      var data = {
        'date-from': ($scope.dateFrom)? dateFrom.toYMD() : null,
        'date-to': dateTo.toYMD()
      };

      $http.get('srv/loader.php?requri=expenses/view', {'params': data})
           .then(function successfullRequest(response){
             $scope.recordsReceived = response.data;
             $scope.records = response.data;

             $scope.typesEvents.onSelectionChanged();
           },function failedRequest(response){
             //console.log('filtered view data load failed');
           });
    }
  }

   $http.get('srv/loader.php?requri=expenses/types')
        .then(function successfullRequest(response){
          $scope.types = response.data;
        },function failedRequest(response){
          //console.log('types load failed');
        });

    $scope.loadResults();
    $scope.dateTo = new Date();

});


app.controller('viewLongTermExpensesCtrl', function($rootScope, $scope, $http){
  $scope.typesSelected = [];
  $scope.records = [];
  $scope.recordsReceived = [];
  $scope.order = "date";
  $scope.reverse = true;
  $scope.dateTo = null;
  $scope.dateFrom = null;
  $scope.selectedRow = null;
  $scope.modalVisible = false;

  $scope.typesEvents = {onSelectionChanged: function(){
    var filtered = [];
    var selLength = $scope.typesSelected.length;

    $scope.recordsReceived.forEach(function(value, index, object){
      if(value.types instanceof Array){
        var valueTypesLength = value.types.length;
        var matched = 0;

        //loop throught types in result rows
        for(var c=0; c < valueTypesLength; c++){

            //loop throught selected types
            for(var i=0; i < selLength; i++){
              if($scope.typesSelected[i].id == value.types[c].id){
                matched++;
              }
            }
        }

        //chceck if everithig was matched
        if(matched == selLength){
          filtered.push(value);
        }
      }else if(selLength == 0){
        filtered.push(value);
      }

      $scope.records = filtered;
    });
  }};

  $scope.showDetails = function(record){
    $scope.selectedRow = record;
    $scope.modalVisible = true;
  }

  $scope.recordDelete = function(){
    if($scope.selectedRow.id){
      $http.post('srv/loader.php?requri=long-term-expenses/delete', {'id': $scope.selectedRow.id})
           .then(function successfullRequest(response){
             $scope.loadResults();
             $scope.modalVisible = false;
           }, function failedRequest(response){
             //console.log('delete failed');
           });
    }
  }

  $scope.loadResults = function(){

    var dateFrom = new Date($scope.dateFrom);
    var dateTo = new Date($scope.dateTo);

    if(!$scope.dateFrom && !$scope.dateTo){
      //date is not set
      $http.get('srv/loader.php?requri=long-term-expenses/view')
           .then(function successfullRequest(response){
             $scope.recordsReceived = response.data;
             $scope.records = response.data;

             $scope.typesEvents.onSelectionChanged();
           },function failedRequest(response){
             //console.log('view data load failed');
           });
    }else if(dateFrom > dateTo){
      $scope.sortExpenseForm.dateFrom.$setValidity("dateToGrDateFrom", false);
    }else{
      $scope.sortExpenseForm.dateFrom.$setValidity("dateToGrDateFrom", true);

      var data = {
        'date-from': ($scope.dateFrom)? dateFrom.toYMD() : null,
        'date-to': dateTo.toYMD()
      };

      $http.get('srv/loader.php?requri=long-term-expenses/view', {'params': data})
           .then(function successfullRequest(response){
             $scope.recordsReceived = response.data;
             $scope.records = response.data;

             $scope.typesEvents.onSelectionChanged();
           },function failedRequest(response){
             //console.log('filtered view data load failed');
           });
    }
  }

   $http.get('srv/loader.php?requri=long-term-expenses/types')
        .then(function successfullRequest(response){
          $scope.types = response.data;
        },function failedRequest(response){
          //console.log('types load failed');
        });

    $scope.loadResults();
    $scope.dateTo = new Date();

});


app.controller('viewIncomesCtrl', function($rootScope, $scope, $http){
  $scope.typesSelected = [];
  $scope.records = [];
  $scope.recordsReceived = [];
  $scope.order = "date";
  $scope.reverse = true;
  $scope.dateTo = null;
  $scope.dateFrom = null;
  $scope.selectedRow = null;
  $scope.modalVisible = false;

  $scope.typesEvents = {onSelectionChanged: function(){
    var filtered = [];
    var selLength = $scope.typesSelected.length;

    $scope.recordsReceived.forEach(function(value, index, object){
      if(value.types instanceof Array){
        var valueTypesLength = value.types.length;
        var matched = 0;

        //loop throught types in result rows
        for(var c=0; c < valueTypesLength; c++){

            //loop throught selected types
            for(var i=0; i < selLength; i++){
              if($scope.typesSelected[i].id == value.types[c].id){
                matched++;
              }
            }
        }

        //chceck if everithig was matched
        if(matched == selLength){
          filtered.push(value);
        }
      }else if(selLength == 0){
        filtered.push(value);
      }

      $scope.records = filtered;
    });
  }};

  $scope.showDetails = function(record){
    $scope.selectedRow = record;
    $scope.modalVisible = true;
  }

  $scope.recordDelete = function(){
    if($scope.selectedRow.id){
      $http.post('srv/loader.php?requri=incomes/delete', {'id': $scope.selectedRow.id})
           .then(function successfullRequest(response){
             $scope.loadResults();
             $scope.modalVisible = false;
           }, function failedRequest(response){
             //console.log('delete failed');
           });
    }
  }

  $scope.loadResults = function(){

    var dateFrom = new Date($scope.dateFrom);
    var dateTo = new Date($scope.dateTo);

    if(!$scope.dateFrom && !$scope.dateTo){
      //date is not set
      $http.get('srv/loader.php?requri=incomes/view')
           .then(function successfullRequest(response){
             $scope.recordsReceived = response.data;
             $scope.records = response.data;

             $scope.typesEvents.onSelectionChanged();
           },function failedRequest(response){
             //console.log('view data load failed');
           });
    }else if(dateFrom > dateTo){
      $scope.sortExpenseForm.dateFrom.$setValidity("dateToGrDateFrom", false);
    }else{
      $scope.sortExpenseForm.dateFrom.$setValidity("dateToGrDateFrom", true);

      var data = {
        'date-from': ($scope.dateFrom)? dateFrom.toYMD() : null,
        'date-to': dateTo.toYMD()
      };

      $http.get('srv/loader.php?requri=incomes/view', {'params': data})
           .then(function successfullRequest(response){
             $scope.recordsReceived = response.data;
             $scope.records = response.data;

             $scope.typesEvents.onSelectionChanged();
           },function failedRequest(response){
             //console.log('filtered view data load failed');
           });
    }
  }

   $http.get('srv/loader.php?requri=incomes/types')
        .then(function successfullRequest(response){
          $scope.types = response.data;
        },function failedRequest(response){
          //console.log('types load failed');
        });

    $scope.loadResults();
    $scope.dateTo = new Date();

});


app.controller('viewDebtsCtrl', function($rootScope, $scope, $http){
  $scope.typesSelected = [];
  $scope.records = [];
  $scope.recordsReceived = [];
  $scope.order = "date";
  $scope.reverse = true;
  $scope.dateTo = null;
  $scope.dateFrom = null;
  $scope.selectedRow = null;
  $scope.modalVisible = false;

  $scope.typesEvents = {onSelectionChanged: function(){
    var filtered = [];
    var selLength = $scope.typesSelected.length;

    $scope.recordsReceived.forEach(function(value, index, object){
      if(value.types instanceof Array){
        var valueTypesLength = value.types.length;
        var matched = 0;

        //loop throught types in result rows
        for(var c=0; c < valueTypesLength; c++){

            //loop throught selected types
            for(var i=0; i < selLength; i++){
              if($scope.typesSelected[i].id == value.types[c].id){
                matched++;
              }
            }
        }

        //chceck if everithig was matched
        if(matched == selLength){
          filtered.push(value);
        }
      }else if(selLength == 0){
        filtered.push(value);
      }

      $scope.records = filtered;
    });
  }};

  $scope.showDetails = function(record){
    $scope.selectedRow = record;
    $scope.modalVisible = true;
  }

  $scope.recordDelete = function(){
    if($scope.selectedRow.id){
      $http.post('srv/loader.php?requri=debts/delete', {'id': $scope.selectedRow.id})
           .then(function successfullRequest(response){
             $scope.loadResults();
             $scope.modalVisible = false;
           }, function failedRequest(response){
             //console.log('delete failed');
           });
    }
  }

  $scope.loadResults = function(){

    var dateFrom = new Date($scope.dateFrom);
    var dateTo = new Date($scope.dateTo);

    if(!$scope.dateFrom && !$scope.dateTo){
      //date is not set
      $http.get('srv/loader.php?requri=debts/view')
           .then(function successfullRequest(response){
             $scope.recordsReceived = response.data;
             $scope.records = response.data;

             $scope.typesEvents.onSelectionChanged();
           },function failedRequest(response){
             //console.log('view data load failed');
           });
    }else if(dateFrom > dateTo){
      $scope.sortExpenseForm.dateFrom.$setValidity("dateToGrDateFrom", false);
    }else{
      $scope.sortExpenseForm.dateFrom.$setValidity("dateToGrDateFrom", true);

      var data = {
        'date-from': ($scope.dateFrom)? dateFrom.toYMD() : null,
        'date-to': dateTo.toYMD()
      };

      $http.get('srv/loader.php?requri=debts/view', {'params': data})
           .then(function successfullRequest(response){
             $scope.recordsReceived = response.data;
             $scope.records = response.data;

             $scope.typesEvents.onSelectionChanged();
           },function failedRequest(response){
             //console.log('filtered view data load failed');
           });
    }
  }

   $http.get('srv/loader.php?requri=debts/types')
        .then(function successfullRequest(response){
          $scope.types = response.data;
        },function failedRequest(response){
          //console.log('types load failed');
        });

    $scope.loadResults();
    $scope.dateTo = new Date();

});


app.controller('viewClaimsCtrl', function($rootScope, $scope, $http){
  $scope.typesSelected = [];
  $scope.records = [];
  $scope.recordsReceived = [];
  $scope.order = "date";
  $scope.reverse = true;
  $scope.dateTo = null;
  $scope.dateFrom = null;
  $scope.selectedRow = null;
  $scope.modalVisible = false;

  $scope.typesEvents = {onSelectionChanged: function(){
    var filtered = [];
    var selLength = $scope.typesSelected.length;

    $scope.recordsReceived.forEach(function(value, index, object){
      if(value.types instanceof Array){
        var valueTypesLength = value.types.length;
        var matched = 0;

        //loop throught types in result rows
        for(var c=0; c < valueTypesLength; c++){

            //loop throught selected types
            for(var i=0; i < selLength; i++){
              if($scope.typesSelected[i].id == value.types[c].id){
                matched++;
              }
            }
        }

        //chceck if everithig was matched
        if(matched == selLength){
          filtered.push(value);
        }
      }else if(selLength == 0){
        filtered.push(value);
      }

      $scope.records = filtered;
    });
  }};

  $scope.showDetails = function(record){
    $scope.selectedRow = record;
    $scope.modalVisible = true;
  }

  $scope.recordDelete = function(){
    if($scope.selectedRow.id){
      $http.post('srv/loader.php?requri=claims/delete', {'id': $scope.selectedRow.id})
           .then(function successfullRequest(response){
             $scope.loadResults();
             $scope.modalVisible = false;
           }, function failedRequest(response){
             //console.log('delete failed');
           });
    }
  }

  $scope.loadResults = function(){

    var dateFrom = new Date($scope.dateFrom);
    var dateTo = new Date($scope.dateTo);

    if(!$scope.dateFrom && !$scope.dateTo){
      //date is not set
      $http.get('srv/loader.php?requri=claims/view')
           .then(function successfullRequest(response){
             $scope.recordsReceived = response.data;
             $scope.records = response.data;

             $scope.typesEvents.onSelectionChanged();
           },function failedRequest(response){
             //console.log('view data load failed');
           });
    }else if(dateFrom > dateTo){
      $scope.sortExpenseForm.dateFrom.$setValidity("dateToGrDateFrom", false);
    }else{
      $scope.sortExpenseForm.dateFrom.$setValidity("dateToGrDateFrom", true);

      var data = {
        'date-from': ($scope.dateFrom)? dateFrom.toYMD() : null,
        'date-to': dateTo.toYMD()
      };

      $http.get('srv/loader.php?requri=claims/view', {'params': data})
           .then(function successfullRequest(response){
             $scope.recordsReceived = response.data;
             $scope.records = response.data;

             $scope.typesEvents.onSelectionChanged();
           },function failedRequest(response){
             //console.log('filtered view data load failed');
           });
    }
  }

   $http.get('srv/loader.php?requri=claims/types')
        .then(function successfullRequest(response){
          $scope.types = response.data;
        },function failedRequest(response){
          //console.log('types load failed');
        });

    $scope.loadResults();
    $scope.dateTo = new Date();

});


app.controller('settingsCtrl', function($rootScope, $scope, $http, $timeout){
    $scope.inputs = [];

    var loadContent = function(){
      $scope.inputs = [];

      $http.get('srv/loader.php?requri=types')
           .then(function successfullRequest(response){
             $scope.inputs = response.data;
           },function failedRequest(response){
             //console.log('types load failed');
           });
    }

    $scope.deleteType = function(rowID){
      if($scope.inputs[rowID].id == null){
          $scope.inputs.splice(rowID, 1);
      }else if($scope.inputs[rowID]['delete']){
        $scope.inputs[rowID]['delete'] = false;
      }else{
        $scope.inputs[rowID]['delete'] = true;
      }
    }

    $scope.addField = function(){
       var input = {id: null, name: '', forType: [false,false,false,false,false]}
       $scope.inputs.push(input);
    }

    $scope.typesSave = function(){
      $http.post('srv/loader.php?requri=types/update', {types: $scope.inputs})
           .then(function successfullRequest(response){
             //console.log(response);
             loadContent();

             $rootScope.infoBox = {visible: true, success: true};
             $timeout(function(){
               $rootScope.infoBox = {visible: false, success: true};
             }, 600);
           }, function failedRequest(response){
             loadContent();

             $rootScope.infoBox = {visible: true, success: false};
             $timeout(function(){
               $rootScope.infoBox = {visible: false, success: false};
             }, 1200);
           });
    }

    loadContent();
});
