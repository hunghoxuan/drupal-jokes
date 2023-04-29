/**
 * @file
 * Jokes API js.
 */
(function (drupalSettings) {

  // pull in the needed values and set the vars
  const api_url = drupalSettings.config.api_url;
  console.log(JSON.stringify(drupalSettings.config));
  const jokeCont = document.getElementById('jokes');

  //console.log(JokesApi::PARAM_API_URL, api_url);

  // use the fetch API to pull in the feed data
  // reject the promise if an ok (200-299) response isn't received
  fetch(api_url, {method: 'get'})
    .then((response) => {
      if (response.ok) {
        return response.json();
      } else if(response.status === 404) {
        return Promise.reject('404 - URL not found!');
      } else {
        return Promise.reject(response.status);
      }
    })
    .then((data) => {
        if (data.value) {
            let jokesTxt1 = document.createElement('p');
            jokesTxt1.textContent += data.value;
            jokeCont.appendChild(jokesTxt1);
        } else {
            let jokesTxt1 = document.createElement('p');
            jokesTxt1.textContent += "No jokes found";
            jokeCont.appendChild(jokesTxt1);
        }
    
  })
  .catch(error => console.log(error));
        
})(drupalSettings);
