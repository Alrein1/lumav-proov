# Kirjeldus
Magento, Alokai ja Nuxt+vue põhine rakendus. Koosneb kahest vidinast 1) kuvab hetke temperatuuri otsitavas linnas (OpenWeatherAPI), 2) kuvab kõik magento platvormile lisatud kategooriad (sample data, kuvamakas middleware-i toimimist)

#Installeerimine
### Nõudmised:
- Node >= 18
- Docker >= 6GB allokeeritud mäluga
- Yarn

## 1) Magento paigaldus
   Klooni repo ning paigalda Magento
   ```
   git clone https://github.com/Alrein1/lumav-proov.git
   cd magento-backend
   bin/setup [url]
   ```

## 2) Middleware konfigureerimine
   Juurkataloogist
   ```
   cd middleware
   yarn install
   cp .env.sample .env
  ```
   Vajadusel muuda magento(vaikimisi magento.test)/middleware(vaikimisi localhost:8181) /nuxt(vaikimisi localhost:3000) url.
   Käivita middleware.
   ```
   yarn start
   ```
## ## 4) Nuxt paigaldamine
   Juurkataloogist
   ```
   cd nuxt-app
   yarn install
   cp .env.sample .env
   ```
   Konfigureeri OpenWeatherAPI võti
   Käivita nuxt server
   ```
   yarn dev
   ```

# Kommentaar
Kuna Dockeri kasutamine oli võõras, läks pealmiselt aega selle paigaldamisele ning Magento opensearch-elasticsearchi peale üleviimisele. Samuti Magento-Alokai integratsioon polnud esialgu kuigi arusaadav, millele ei aidanud kaasa, et osa info on aegunud või põhineb maksumüüritaga oleva integratsioonil. Kuigi vue oli võõras, siis koodi poole pealt eriti probleeme polnud.
ChatGPT-d kasutasin ülevaate saamiseks kuidas antud töökeskkond peaks umbes välja nägema. Lisaks jooksvalt kui probleeme tekkis. Nt
```Explain in detail how to get Magento working with Alokai based on https://github.com/vuestorefront/magento2. I have Magento installed on Docker. I want to use Nuxt for front-end.```
