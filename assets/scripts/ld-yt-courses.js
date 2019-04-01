jQuery(function($) {
  $('.submit-query-search').on('click', function(e) {
    e.preventDefault();

    var link = $('#search_field').val();

    var currentURL = $('#youtube_search').attr('action');

    console.log(link);

    $.ajax({
      type: 'POST',
      url: ld_yt_courses.ajaxurl,
      data: {
        action: 'fetch_youtube_link',
        link: link,
        currentURL: currentURL
      },
      /*xhrFields: { 
        withCredentials: true,
      },*/
      success: function(data) {
        $('.search-results').html(data);
      },
    });

    return false;
  });


  jQuery('.video-search').on('click', '.course_create', function(e) {
    e.preventDefault();

    var coursedata = {};
    var lessondata = {};
    var counter = 0;

    jQuery('.video-search').find('.video-container').each(function() {
      var values = {};
      jQuery(this).find('input[type="hidden"]').each(function() {
        var name = jQuery(this).attr('name');
        values[name] = jQuery(this).val();
      });
      lessondata[counter] = values;
      counter++;
    });

    coursedata['title'] = jQuery('input[name="course_title"]').val();
    coursedata['description'] = jQuery('textarea[name="course_description"]').val();
    coursedata['url'] = jQuery('input[name="course_url"]').val();

    $.ajax({
      type: 'POST',
      url: ld_yt_courses.ajaxurl,
      data: {
        action: 'create_youtube_courses',
        CourseData: coursedata,
        LessonData: lessondata,
      },
      success: function(data) {
        $('.search-results').html(data);
      },
    });

    return false;
  });

  jQuery('.video-search').on('click', '.course_create_individual', function(e) {
    e.preventDefault();

    var coursedata = {};
    var lessondata = {};
    var counter = 0;

    jQuery('.video-search').find('.video-container-individual').each(function() {
      var values = {};
      jQuery(this).find('input[type="hidden"]').each(function() {
        var name = jQuery(this).attr('name');
        values[name] = jQuery(this).val();
      });
      lessondata[counter] = values;
      counter++;
    });

    coursedata['title'] = jQuery('input[name="course_title"]').val();
    coursedata['description'] = jQuery('textarea[name="course_description"]').val();
    coursedata['url'] = jQuery('input[name="course_url"]').val();

    $.ajax({
      type: 'POST',
      url: ld_yt_courses.ajaxurl,
      data: {
        action: 'create_youtube_courses',
        CourseData: coursedata,
        LessonData: lessondata,
      },
      success: function(data) {
        $('.search-results').html(data);
      },
    });

    return false;
  });

  jQuery(document).ready(function() {
    if(jQuery('#search_field').val() != '') {
      console.log(jQuery('#search_field').val());
      jQuery('.submit-query-search').trigger('click');
    }
  });

  
});
