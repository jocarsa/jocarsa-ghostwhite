(function() {
  // Get the 'user' parameter from this script tag's URL query string
  var scriptEl = document.currentScript;
  var userParam = "";
  if (scriptEl) {
    var src = scriptEl.src;
    var parts = src.split("?");
    if (parts.length > 1) {
      var params = new URLSearchParams(parts[1]);
      userParam = params.get("user") || "";
    }
  }

  // Prepare a performance timing summary if available
  var performanceTiming = {};
  if (window.performance && window.performance.timing) {
    var timing = window.performance.timing;
    performanceTiming = {
      navigationStart: timing.navigationStart,
      domContentLoadedEventEnd: timing.domContentLoadedEventEnd,
      loadEventEnd: timing.loadEventEnd
    };
  }

  // Collect browser and device data with additional parameters
  const data = {
    user: userParam,
    user_agent: navigator.userAgent,
    screen_width: window.screen.width,
    screen_height: window.screen.height,
    viewport_width: window.innerWidth,
    viewport_height: window.innerHeight,
    language: navigator.language,
    languages: navigator.languages ? navigator.languages.join(', ') : '',
    timezone_offset: new Date().getTimezoneOffset(),
    platform: navigator.platform,
    connection_type: (navigator.connection && navigator.connection.effectiveType) ? navigator.connection.effectiveType : 'unknown',
    screen_color_depth: window.screen.colorDepth,
    url: window.location.href,
    referrer: document.referrer,
    timestamp: new Date().toISOString(),
    performance_timing: performanceTiming
  };

  // Send the collected data to the logging endpoint (adjust the URL if needed)
  fetch('log.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  }).catch(function(error) {
    console.error('Analytics error:', error);
  });
})();

