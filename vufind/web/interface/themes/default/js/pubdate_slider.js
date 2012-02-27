YAHOO.util.Event.onDOMReady(function () {
    // create the slider for any date facets found
    var Dom = YAHOO.util.Dom;
    var sliders = Dom.getElementsByClassName('dateSlider');
    for (var i = 0; i < sliders.length; i++) {
        var prefix = sliders[i].id.substr(0, sliders[i].id.length - 6);
        makePublishDateSlider(prefix);
    }
});

function makePublishDateSlider(prefix)
{
    var Dom = YAHOO.util.Dom;
    var slider_bg = Dom.get(prefix + "Slider"),
        from    = Dom.get(prefix + "from"),
        to      = Dom.get(prefix + "to");

    // Slider: allow tabs to overlap, set tickSize, set range
    var minThumbDistance = -20,
        tickSize = 0,
        range = 150;

    // assuming our oldest item is published in the 15th century
    var initMin = 1500;
    var min = initMin;

    // move the min 20 years away from the "from" value
    if (from.value > min + 20) {
        min = from.value - 20;
    }

    // and keep the max at 1 years from now
    var max = new Date().getFullYear() + 1;

    if (!to.value) {
        initMaxVal = max;
    } else {
        initMaxVal = to.value;
    }

    // get conversion factor
    var cf = getConversionFactor(min, max, range);

    // initial slider range pixel offset values
    var initValues = [convertDown(from.value, cf, min), convertDown(initMaxVal, cf, min)];

    // Create the DualSlider
    var slider = YAHOO.widget.Slider.getHorizDualSlider(slider_bg,
        prefix + "slider_min_thumb", prefix + "slider_max_thumb",
        range, tickSize, initValues);

    slider.minRange = minThumbDistance;

    // Only display slider if javascript enabled
    slider_bg.style.display = 'block';

    // Handle events
    slider.minSlider.subscribe("change", function() {
        // move the min 20 years away from the "from" value
        from.value = convertUp(slider.minVal, cf, min);
    });

    slider.maxSlider.subscribe("change", function() {
       if (!to.value || to.value > max) {
           to.value = max;
       }
       to.value = convertUp((slider.maxVal), cf, min);
    });


    // when user enters values into the boxes
    // the slider needs to be updated too
    var ids = [prefix + 'from', prefix + 'to']
    YAHOO.util.Event.on(ids, 'change', function() {
        // make sure that to is always the higher value
        if (parseInt(from.value) > parseInt(to.value)) {
            var hold = from.value;
            from.value = to.value;
            to.value = hold;
        }

        // the user may have entered a from value below the
        // current min value
        if (min > initMin){
            if (from.value > (initMin + 20)) {
                min = from.value - 20;
            } else {
            min = initMin;
            }
        }

        //store the values entered by the user
        var fromVal = from.value;
        var toVal = to.value;

        cf = (max - min)/(range);
        slider.setValues(convertDown(from.value, cf, min),
            convertDown(to.value, cf, min));

        // Make sure the convert function hasn't change the
        // values entered by the user
        from.value = fromVal;
        to.value = toVal;
    });
}


// Get the conversion factor from 0-range pixels to year values
function getConversionFactor(min, max, range) {
    return (max - min)/(range);
}

// Function to convert from slider range values to year values
function convertUp(val, cf, min) {
    return Math.round(val * cf + min);
}

// Function to convert from year values to slider range values
function convertDown(val, cf, min) {
    return Math.round((val - min) / cf);
}

