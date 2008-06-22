function toggle_regex ()
{
  Element.toggle ('regex-options');
}

function regex_replace (classs, item, offset, length, replace)
{
  // Need an ID, start, end, and replace
  new Ajax.Request (wp_base + '?cmd=regex_replace&id=' + item,
    {
      asynchronous: true,
      evalScripts: true,
      parameters: { klass: classs, item: item, offset: offset, length: length, replace: replace },
      onLoading: function(request)
      {
        $('options_' + item + '_' + offset).innerHTML = '<img src="' + wp_loading + '" alt="loading" width="16" height="16"/>';
      },
      onComplete: function(request)
      {
        new Effect.Fade ('search_' + item + '_' + offset, { to: 0.2});
        new Effect.Fade ('options_' + item + '_' + offset);
      }
    });
}

function regex_edit_replace (classs, item, offset, length)
{
  $('replace_' + item + '_' + offset).innerHTML = re_replace[item + '_' + offset];
  $('replace_' + item + '_' + offset).innerHTML += ' <input type="submit" name="cancel" value="Cancel" onclick="regex_cancel_replace(' + item + ',' + offset + ')"/>';
  Field.activate ('rep_' + item + '_' + offset);
}

function regex_edit (classs, item, offset, length)
{
  $('value_' + item + '_' + offset).innerHTML = re_input[item + '_' + offset];
  $('value_' + item + '_' + offset).innerHTML += ' <input type="submit" name="cancel" value="Cancel" onclick="regex_cancel(' + item + ',' + offset + ')"/>';
  Field.activate ('txt_' + item + '_' + offset);
}

function regex_cancel (item, offset)
{
  $('value_' + item + '_' + offset).innerHTML = re_text[item + '_' + offset].gsub ('&quot;', '"');
}

function regex_cancel_replace (item, offset)
{
  $('replace_' + item + '_' + offset).innerHTML = re_text_replace[item + '_' + offset].gsub ('&quot;', '"');
}

function save_edit (classs, item, offset, start, length)
{
  new Ajax.Request (wp_base + '?cmd=regex_replace&id=' + item,
    {
      asynchronous: true, evalScripts: true,
      parameters: { klass: classs, item: item, offset: start, length: length, replace: $('txt_' + item + '_' + offset).value},
      onLoading: function(request) { $('options_' + item + '_' + offset).innerHTML = '<img src="' + wp_loading + '" alt="loading" width="16" height="16"/>'; },
      onComplete: function(request)
        {
          new Effect.Fade ('search_' + item + '_' + offset, { to: 0.2});
          new Effect.Fade ('options_' + item + '_' + offset);
          $('value_' + item + '_' + offset).innerHTML = $('txt_' + item + '_' + offset).value;
        }
    });
}

function save_edit_rep (classs, item, offset, start, length)
{
  new Ajax.Request (wp_base + '?cmd=regex_replace&id=' + item,
    {
      asynchronous: true, evalScripts: true,
      parameters: { klass: classs, item: item, offset: start, length: length, replace: $('rep_' + item + '_' + offset).value},
      onLoading: function(request) { $('options_' + item + '_' + offset).innerHTML = '<img src="' + wp_loading + '" alt="loading" width="16" height="16"/>'; },
      onComplete: function(request)
        {
          new Effect.Fade ('search_' + item + '_' + offset, { to: 0.2});
          new Effect.Fade ('options_' + item + '_' + offset);
          $('replace_' + item + '_' + offset).innerHTML = $('rep_' + item + '_' + offset).value;
        }
    });
}