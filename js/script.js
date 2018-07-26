"use strict";

var UI_URL = '/apps/MyFamily/ui.php';
var API_URL = "/apps/MyFamily/api.php";

/**
 * Set up submenu display handlers.
 */
function setupSubMenuDisplayHandlers() {
  var menuItems = document.getElementsByClassName("menuItem");
  for (var i = 0; i < menuItems.length; i++) {
    menuItems[i].addEventListener("click", function() {
      var subMenu = this.nextElementSibling;
      if (subMenu.style.display == "block") {
        subMenu.style.display = "none";
      }
      else {
        subMenu.style.display = "block";
      }
    });
  }
}

/**
 * Handles the menu selection.
 */
function handleMenuSelection(cmd) {
  var form = document.getElementById('menuForm');
  if (form) {
    var cmdParam = form.elements['cmd'];
    cmdParam.value = cmd;
    form.submit();
  }
}

/**
 * Updates the UI content.
 */
function updateContent(content) {
  var contentElem = document.getElementById('content');
  if (contentElem) {
    contentElem.innerHTML = content;
  }
}

/**
 * Shows the family tree based on the supplied data.
 * 
 * @param familyTree: The family tree data.
 */
function showFamilyTree(familyTree) {
  if (familyTree) {
    var chartConfig = {
      container: '#familyTreeChart',
      scrollbar: 'fancy',
      connectors: {type: 'step'},
      animateOnInit: true,
      animation: {nodeAnimation: 'easeOutBounce', nodeSpeed: 700, connectorsAnimation: 'bounce', connectorSpeed: 700},
      node: {HTMLclass: 'chartNode'}
    };
    var chartData = [chartConfig];
    var nodes = {};
    familyTree.forEach(function(member, index, family) {
      var memberID = member.member_id;
      var parentID = (member.parent_id) ? member.parent_id : member.spouse_id;
      var parent = nodes[parentID];
      var location = (member.location) ? member.location : '';
      var orientation = (!member.parent_id && parent) ? 'EAST' : null; // Add horizontal orientation for the second node in a spouse relation
      var link = {id: memberID, href: '#'};
      var node = {
        text: {name: member.name, title: location},
        orientation: orientation,
        link: link
      };
      if (parent) {
        node['parent'] = parent;
      }
      if (member['fb_id']) {
        node['image'] = 'http://graph.facebook.com/' + member['fb_id'] + '/picture';
      }
      //console.log('node: ' + JSON.stringify(node));
      nodes[member.member_id] = node;
      chartData.push(node);
    });
    var tree = new Treant(chartData);
  }
}

/**
 * Shows a normal message.
 */
function showMessage(msg) {
  var msgArea = document.getElementById('messageArea');
  if (msgArea) {
    msgArea.innerHTML = "<div id='message' class='message'>" + msg + "</div>";
    setTimeout(clearMessage, 3000);
  }
}

/**
 * Shows an error message.
 */
function showError(msg) {
  var msgArea = document.getElementById('messageArea');
  if (msgArea) {
    msgArea.innerHTML = "<div id='message' class='error'>" + msg + "</div>";
    setTimeout(clearMessage, 6000);
  }
}

/**
 * Clears the message.
 */
function clearMessage() {
  var msgArea = document.getElementById('messageArea');
  if (msgArea) {
    msgArea.innerHTML = "";
  }
}

jQuery(document).ready(function($) {
  
  //
  // Common AJAX Setup
  //
  $(document).ajaxStart(function() {
    $('#processing').css('display', 'block');
  });
  $(document).ajaxComplete(function() {
    $('#processing').css('display', 'none');
  });
  $(document).ajaxError(function(event, xhr, ajaxOptions, thrownError) {
    if (xhr.responseText) {
      var response = JSON.parse(xhr.responseText);
      if (response && ('error' in response)) {
        showError(response.error);
      }
      else {
        showError(xhr.responseText);
      }
    }
    else {
      showError('An error has occurred while processing.');
    }
  });
  
  //
  // Action handlers
  //
  
  /* Handle the menu actions. */
  $(document).on('click', '.menuAction', function() {
    $('#menu').find('.menuAction').removeClass('activeMenuAction'); // Clear prior selection
    $(this).toggleClass('activeMenuAction'); // Highlight the current menu action
    $('#cmd').val($(this).attr('id'));
    $.ajax({
      url: UI_URL,
      type: 'POST',
      data: $('#menuForm').serialize(),
      success: function(response) {
        updateContent(response);
      }
    });
    return false;
  });
  
  /* Handle the edit family action. */
  $(document).on('click', '#editFamily', function() {
    $.ajax({
      url: UI_URL,
      type: 'POST',
      data: $('#manageFamiliesForm').serialize(),
      success: function(response) {
        updateContent(response);
      }
    });
    return false;
  });
  
  /* Handle the delete family action. */
  $(document).on('click', '#deleteFamily', function() {
    var result = confirm('Are you sure?');
    if (!result) {
      return false;
    }
    $('#manageFamiliesForm input[name=cmd]').val('deleteFamily');
    $.ajax({
      url: API_URL,
      type: 'POST',
      data: $('#manageFamiliesForm').serialize(),
      success: function(response) {
        showMessage(response['message']);
        updateContent('');
      }
    });
    return false;
  });
  
  /* Handle the edit family member action. */
  $(document).on('click', '.editFamilyMember', function() {
    $('#familyMemberSelectionForm input[name=cmd]').val('showEditFamilyMemberForm');
    $('#member_id').val($(this).attr('id'));
    $.ajax({
      url: UI_URL,
      type: 'POST',
      data: $('#familyMemberSelectionForm').serialize(),
      success: function(response) {
        updateContent(response);
      }
    });
    return false;
  });
  
  /* Handle the delete family member action. */
  $(document).on('click', '.deleteFamilyMember', function() {
    var result = confirm('Are you sure?');
    if (!result) {
      return false;
    }
    var cmd = $('#familyMemberSelectionForm input[name=cmd]').val();
    $('#familyMemberSelectionForm input[name=cmd]').val('deleteFamilyMember');
    $('#member_id').val($(this).attr('id'));
    var data = $('#familyMemberSelectionForm').serialize();
    $('#familyMemberSelectionForm input[name=cmd]').val(cmd);
    $.ajax({
      url: API_URL,
      type: 'POST',
      data: data,
      success: function(response) {
        showMessage(response['message']);
      }
    });
    return false;
  });
  
  /* Handle the add question action. */
  $(document).on('click', '#addQuestion', function() {
    $.ajax({
      url: UI_URL,
      type: 'POST',
      data: $('#getQuestionsForm').serialize(),
      success: function(response) {
        $('#memberResponses')
          .find('tbody')
          .append('<tr><td>' + response + '</td></tr>')
          .append("<tr><td><textarea name='answers[]' rows='5' cols='80'></textarea></td></tr>");
      }
    });
    return false;
  });
  
  /* Handle the edit member responses action. */
  $(document).on('click', '.editMemberResponses', function() {
    $('#familyMemberSelectionForm input[name=cmd]').val('showMemberResponsesForm');
    $('#member_id').val($(this).attr('id'));
    $.ajax({
      url: UI_URL,
      type: 'POST',
      data: $('#familyMemberSelectionForm').serialize(),
      success: function(response) {
        updateContent(response);
      }
    });
    return false;
  });
  
  /* Handle the show member details action. */
  $(document).on('click', '.chartNode', function() {
    $('#familyTreeForm input[name=cmd]').val('showMemberDetails');
    $('#member_id').val($(this).attr('id'));
    $.ajax({
      url: UI_URL,
      type: 'POST',
      data: $('#familyTreeForm').serialize(),
      success: function(response) {
        updateContent(response);
      }
    });
    return false;
  });
  
  
  //
  // API calls
  //
  
  /* Handles the user form submission. */
  $(document).on('submit', '.crudForm', function() {
    $.ajax({
      url: API_URL,
      type: 'POST',
      data: $(this).serialize(),
      success: function(response) {
        showMessage(response['message']);
      }
    });
    return false;
  });
  
  /* Handles the family member form submission. */
  $(document).on('submit', '#familyMemberForm', function() {
    // Check which submit button was clicked
    var submitID = $(this).find('input[type=submit]:focus').attr('id');
    // Check if it's an eligible request for API call, otherwise process it as a UI call
    if (submitID != 'showAddFamilyMemberForm' && $('#source_member_id').length > 0) {
      var disabled = $('#family_id').attr('disabled');
      $('#family_id').attr('disabled', false); // Temporarily enable field (if disabled) to ensure it's passed in form data
      var data = $('#familyMemberForm').serialize();
      $('#family_id').attr('disabled', disabled);
      $.ajax({
        url: API_URL,
        type: 'POST',
        data: data,
        success: function(response) {
          showMessage(response['message']);
        }
      });
    }
    else {
      // Process as a UI request
      if (submitID == 'showAddFamilyMemberForm') {
        $('#familyMemberForm input[name=cmd]').val('showAddFamilyMemberForm');
      }
      $.ajax({
        url: UI_URL,
        type: 'POST',
        data: $('#familyMemberForm').serialize(),
        success: function(response) {
          updateContent(response);
        }
      });
    }
    return false;
  });
  
  /* Handles the family member selection form submission. */
  $(document).on('submit', '#familyMemberSelectionForm', function() {
    // Check which submit button was clicked
    var submitID = $(this).find('input[type=submit]:focus').attr('id');
    // Check if it's an eligible request for API call, otherwise process it as a UI call
    if (submitID != 'manageFamilyMembers' && $('#member_id').length > 0) {
      $.ajax({
        url: API_URL,
        type: 'POST',
        data: $('#familyMemberSelectionForm').serialize(),
        success: function(response) {
          showMessage(response['message']);
        }
      });
    }
    else {
      // Process as a UI request
      if (submitID == 'manageFamilyMembers') {
        $('#familyMemberForm input[name=cmd]').val('manageFamilyMembers');
      }
      $.ajax({
        url: UI_URL,
        type: 'POST',
        data: $('#familyMemberSelectionForm').serialize(),
        success: function(response) {
          updateContent(response);
        }
      });
    }
    return false;
  });
  
  /* Handles the family tree form submission. */
  $(document).on('submit', '#familyTreeForm', function() {
    //e.preventDefault();
    $.ajax({
      url: API_URL,
      type: 'POST',
      data: $('#familyTreeForm').serialize(),
      success: function(response) {
        showFamilyTree(response);
      }
    });
    return false;
  });
});
