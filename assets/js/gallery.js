var cb_gallery;
(function($){
  cb_gallery = {
    onPageLoad : function(){
      $('.cb-gallery-box .attachments.ui-sortable').sortable();
    },
    attachment : {
      remove : function(ths){
        var $ths = $(ths), a_li;
        a_li = $ths.parents('li.attachment');
        a_li.hide('fast', function(){
          $(this).remove();
        })
        return false;
      }
    },
    modal : {
      open : function(ths, options){
        var $ths = $(ths), cb_gallery_box, frame, as;
        cb_gallery_box = $ths.parents('.cb-gallery-box');
        attachments_val = cb_gallery_box.find('.attachments_val');
        attachments = cb_gallery_box.find('.attachments');

        as = {};

        // If the media frame already exists, reopen it.
        if ( wp.media.frames[options.term_id] ) {
          wp.media.frames[options.term_id].open();
          return false;
        }

        // Create the media frame.
        frame = wp.media.frames[options.term_id] = wp.media({
          frame: 'select',
          title: $ths.attr('data-title'),
          multiple: true,
          uploader: true,
          library: {
            uploadedTo: options.post_id,
            orderby: 'menuOrder',
            order:   'ASC'
          },
          button: {
            text: $ths.attr('data-add-button'),
            close: true
          }
        });

        // When an image is selected, run a callback.
        frame.on('select', function() {
          as = {};
          frame.state().get('selection').map(function(a){
            a.token = options.token;
            a.term_id = options.term_id;
            as[a.id] = a;
          });
          _.each(as, function(data){
        	  if(!attachments.find('#attachment-'+data.id).length){
              data.thumb = (data.attributes.sizes && data.attributes.sizes.thumbnail.url) || data.attributes.icon;
              attachments.append((wp.media.template('gallery-attachment'))(data));
              attachments.find('#attachment-'+data.id).hide().show('fast');
            }
          });

        });

        frame.open();
        return false;
      }
    }
  };
  $(cb_gallery.onPageLoad);
})(jQuery);