<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import InputGeneric from '@/components/input/input-generic.vue'
import InputPassword from '@/components/input/input-password.vue'
import ButtonGeneric from '@/components/button/button-generic.vue'
import separator from '@/components/separator.vue'
import textLink from '@/components/text/text-link.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import router from '@/router'
import TextInfo from '@/components/text/text-info.vue'
import ActionSheet from '@/components/modal/action-sheet.vue'
import { ref } from 'vue'
import usefulFunctions from '@/utils/useful-functions.ts'
import apiService from '@/utils/api/api-service.ts'

const privacyAccepted = ref<boolean>(false)
const tosAccepted = ref<boolean>(false)
const privacyActionSheetDisplayed = ref<boolean>(false)
const tosActionSheetDisplayed = ref<boolean>(false)

const email = ref<string>('')
const password = ref<string>('')
const confirmPassword = ref<string>('')
const username = ref<string>('')

const sent = ref<boolean>(false)

function doAction(name: string) {
  if (name === 'continue') {
    console.log('Signup Continue')
  } else {
    console.log('Action:', name)
  }
}

function openLogin() {
  router.push({ name: 'login' })
}

function doSignup() {
  if (
    !privacyAccepted.value ||
    !tosAccepted.value ||
    sent.value ||
    !validateEmail(email.value) ||
    !validatePassword(password.value) ||
    password.value !== confirmPassword.value ||
    !validateUsername(username.value) ||
    email.value.length === 0 ||
    username.value.length === 0 ||
    confirmPassword.value.length === 0 ||
    password.value.length === 0
  ) {
    return
  }
  sent.value = true
  apiService.signup(email.value, password.value, username.value).then(
    (response) => {
      console.log('>>>', response)
      if (response && response.status === 200 && response.data) {
        usefulFunctions.saveToLocalStorage('login-id', response.data['login-id'])
        router.push({ name: 'signup-verify' })
      } else {
        if (response.status === 409) {
          console.log('Errore: indirizzo email o username già in uso')
        }
        //usefulFunctions.showToast('Errore durante il login: ' + response.message, 'error')
      }
      sent.value = false
    },
    (error) => {
      console.error('Error', error)
      sent.value = false
    },
  )
}

function emailChanged(value: string) {
  email.value = value
}
function passwordChanged(value: string) {
  password.value = value
}
function confirmPasswordChanged(value: string) {
  confirmPassword.value = value
}
function usernameChanged(value: string) {
  username.value = value
}

function validateEmail(email: string): boolean {
  return usefulFunctions.checkEmailValidity(email) || email.length === 0
}

function validatePassword(password: string): boolean {
  return usefulFunctions.checkPasswordValidity(password) || password.length === 0
}

function validateUsername(username: string): boolean {
  return usefulFunctions.checkUsernameValidity(username) || username.length === 0
}

function openPrivacy() {
  privacyActionSheetDisplayed.value = true
}
function openTos() {
  tosActionSheetDisplayed.value = true
}
function closePrivacy() {
  privacyActionSheetDisplayed.value = false
}
function closeTos() {
  tosActionSheetDisplayed.value = false
}

function setTosAccepted(accepted: boolean) {
  tosAccepted.value = accepted
}
function setPrivacyAccepted(accepted: boolean) {
  privacyAccepted.value = accepted
}
</script>

<template>
  <topbar variant="simple-big" title="Nuovo account"></topbar>
  <main>
    <div class="content">
      <text-paragraph align="start">
        Compila tutti i campi per creare un nuovo account.
      </text-paragraph>
      <div class="container">
        <div class="textboxes">
          <input-generic
            icon="email"
            @input="emailChanged($event)"
            placeholder="indirizzo email"
            chars-disallowed=" "
            :error-status="!validateEmail(email)"
            :text="email"
          ></input-generic>
          <div class="info-box">
            <input-password
              @input="passwordChanged($event)"
              placeholder="password"
              :min-length="10"
              chars-disallowed=" "
              :text="password"
              :error-status="!validatePassword(password)"
            ></input-password>
            <text-info>
              deve essere almeno di 10 caratteri e deve contenere almeno una lettera maiuscola, una
              lettera minuscola e un numero
            </text-info>
          </div>
          <input-password
            @input="confirmPasswordChanged($event)"
            placeholder="ripeti password"
            chars-disallowed=" "
            :text="confirmPassword"
            :error-status="confirmPassword !== password || !validatePassword(confirmPassword)"
          ></input-password>
          <div class="info-box">
            <input-generic
              @input="usernameChanged($event)"
              placeholder="username"
              icon="username"
              chars-allowed="abcdefghijklmnopqrstuvwxyz."
              :min-length="5"
              :max-length="20"
              :text="username"
              :error-status="!validateUsername(username)"
            ></input-generic>
            <text-info>
              sarà visibile a tutti gli utenti. Deve avere una lunghezza compresa tra 5 e 20
              caratteri, e può contenere solo lettere (minuscole), numeri e il punto
            </text-info>
          </div>
        </div>
        <div class="buttons">
          <text-paragraph align="start">
            Per poter proseguire, devi accettare l'informativa sulla privacy e i termini d'uso
          </text-paragraph>
          <button-generic
            @action="openPrivacy"
            :icon="privacyAccepted ? 'mark-yes' : 'chevron-up'"
            :variant="privacyAccepted ? 'primary' : 'outline'"
            text="Informativa sulla privacy"
            align="center"
            iconPosition="end"
            :disabled="false"
            :small="true"
          />
          <button-generic
            @action="openTos"
            :icon="tosAccepted ? 'mark-yes' : 'chevron-up'"
            :variant="tosAccepted ? 'primary' : 'outline'"
            text="Termini d'uso"
            align="center"
            iconPosition="end"
            :disabled="false"
            :small="true"
          />
          <div class="info-box">
            <button-generic
              @action="doSignup"
              icon="forward"
              variant="cta"
              text="Prosegui"
              align="center"
              iconPosition="end"
              :disabled="
                !privacyAccepted ||
                !tosAccepted ||
                sent ||
                !validateEmail(email) ||
                !validatePassword(password) ||
                password !== confirmPassword ||
                !validateUsername(username) ||
                email.length === 0 ||
                username.length === 0 ||
                confirmPassword.length === 0 ||
                password.length === 0
              "
            />
            <text-info v-if="!privacyAccepted && !tosAccepted">
              Per proseguire devi accettare l'informativa sulla privacy e i termini d'uso
            </text-info>
            <text-info v-else-if="!privacyAccepted">
              Per proseguire devi accettare anche l'informativa sulla privacy
            </text-info>
            <text-info v-else-if="!tosAccepted">
              Per proseguire devi accettare anche i termini d'uso
            </text-info>
          </div>
        </div>
      </div>
      <separator variant="primary" />
      <text-link text="Hai già un account? Accedi" @action="openLogin" :disabled="sent" />
    </div>
  </main>

  <action-sheet
    v-if="privacyActionSheetDisplayed"
    title="Informativa Privacy"
    :height="80"
    :hiddenByDefault="!privacyActionSheetDisplayed"
    @onclose="closePrivacy"
    button1-text="Rifiuta"
    button1-style="primary"
    @actionButton1="setPrivacyAccepted(false)"
    button2-text="Accetta"
    button2-style="cta"
    @actionButton2="setPrivacyAccepted(true)"
  >
    <text-paragraph align="justify" color="black">
      <p>
        La presente informativa descrive le modalità di gestione dei dati all'interno di
        <strong>Emoticolor</strong>. Seppur il progetto ha principalmente finalità di ricerca, la
        protezione dell'identità dell'utente è una delle priorità del progetto.
      </p>

      <h3>1. Requisiti di Età</h3>
      <p>
        L'accesso al servizio è riservato a utenti che abbiano compiuto almeno
        <strong>14 anni</strong>. Il progetto non richiede né memorizza la data di nascita;
        pertanto, la creazione di un account funge da autocertificazione del possesso dei requisiti
        di età.
      </p>

      <h3>2. Dati Raccolti e Finalità</h3>
      <p>
        Vengono raccolti esclusivamente i dati tecnici minimi per il funzionamento del servizio:
      </p>
      <ul>
        <li><strong>Username:</strong> Unico dato reso pubblico all'interno dell'applicazione.</li>
        <li>
          <strong>Email:</strong> Utilizzata esclusivamente per l'invio di codici OTP (One-Time
          Password) necessari alla sicurezza dell'account, e per l'autenticazione.
        </li>
        <li><strong>Password:</strong> Necessaria per l'autenticazione.</li>
      </ul>

      <h3>3. Sicurezza e Tecnologie di Conservazione</h3>
      <p>
        Per qualunque tipo di richiesta che implica un'interazione diretta con i dati personali, o
        modifica degli stessi, è necessario l'accesso autenticato tramite credenziali valide. Dopo
        la registrazione, dopo ciascun login, per cambiare/resettare la password o per eliminare
        l'account, è attiva l'autenticazione a due fattori (2FA) tramite codice OTP inviato via
        email.
      </p>
      <p>
        Inoltre, il progetto adotta standard di crittografia avanzati per la protezione delle
        informazioni:
      </p>
      <ul>
        <li>
          <strong>Email:</strong> Salvata tramite algoritmo <strong>HMAC (SHA-256)</strong> e
          cifrata con <strong>AES</strong>. Tale sistema garantisce che l'indirizzo sia utilizzato
          solo per scopi di servizio (invio OTP).
        </li>
        <li>
          <strong>Password:</strong> Archiviata in modo sicuro tramite algoritmo
          <strong>Argon2id</strong>.
        </li>
        <li>
          <strong>Identificativi:</strong> Ogni account è associato a un
          <strong>UUID v4</strong> generato casualmente.
        </li>
        <li>
          <strong>Sessione:</strong> Un <em>Login-token</em> (validità 24 ore) e un
          <em>Refresh-token</em> (validità 1 mese) sono salvati localmente sul dispositivo per
          gestire l'accesso.
        </li>
      </ul>

      <h3>4. Contenuti Pubblicati (Post)</h3>
      <p>
        I post e le riflessioni inserite <strong>non sono crittografati</strong>. È responsabilità
        dell'utente evitare l'inserimento di dati personali o sensibili nei contenuti dei post. Una
        volta pubblicato, il post rimane nel database per scopi di ricerca e non può essere rimosso
        o modificato dall'utente, salvo interventi di moderazione.
      </p>

      <h3>5. Anonimato e Responsabilità</h3>
      <p>
        Non venendo richiesti dati anagrafici, l'attività dell'utente non è riconducibile a una
        persona fisica specifica da parte del progetto. La responsabilità legale dei contenuti
        rimane esclusivamente in capo all'autore.
      </p>
      <em>Ultimo aggiornamento: 1 feb 2026</em>
    </text-paragraph>
  </action-sheet>
  <action-sheet
    v-if="tosActionSheetDisplayed"
    title="Termini d'Uso"
    :height="80"
    :hiddenByDefault="!tosActionSheetDisplayed"
    @onclose="closeTos"
    button1-text="Rifiuta"
    button1-style="primary"
    @actionButton1="setTosAccepted(false)"
    button2-text="Accetta"
    button2-style="cta"
    @actionButton2="setTosAccepted(true)"
  >
    <text-paragraph align="justify" color="black">
      <p>
        I presenti Termini d'Uso disciplinano l'accesso e l'utilizzo di <strong>Emoticolor</strong>.
        L'utilizzo del servizio implica l'accettazione delle condizioni qui riportate, pur
        trattandosi di un progetto con finalità di ricerca.
      </p>

      <h3>1. Natura del Progetto e Limitazioni</h3>
      <p>
        Il servizio è fornito "così com'è" per scopi scientifici. Il sistema potrebbe presentare bug
        o vulnerabilità di sicurezza e si declina ogni responsabilità per eventuali perdite di dati
        o malfunzionamenti. Il servizio può essere sospeso o chiuso in qualsiasi momento, anche
        senza comunicazione preventiva. Detto ciò, ci si impegna a mantenere il servizio sicuro,
        adottando un approccio di sviluppo <em>safe-by-design</em>.
      </p>

      <h3>2. Accuratezza dei Contenuti "Impara"</h3>
      <p>
        I contenuti presenti nella sezione "Impara" relativi alle emozioni hanno scopo
        esclusivamente illustrativo e potrebbero non essere sempre scientificamente attendibili.
        All'interno della sezione sono indicate le fonti di riferimento per la consultazione
        originale. Fare riferimento a tali fonti per informazioni accurate e aggiornate, o per
        verificarne la validità.
      </p>

      <h3>3. Funzionamento delle Interazioni e Visibilità</h3>
      <p>
        La gestione della visibilità e delle interazioni segue regole fisse per garantire la
        privacy:
      </p>
      <ul>
        <li>
          <strong>Stati emotivi/Post:</strong> I post "Pubblici" sono visibili a tutti, mentre i
          post "Privati" sono accessibili solo all'autore e non supportano l'inserimento di
          reaction.
        </li>
        <li>
          <strong>Reaction:</strong> L'autore di un post visualizza il numero totale di reaction
          ricevute per ciascuna di esse, ma non l'identità di chi le ha inserite. Le reaction
          espresse sono visibili individualmente solo a chi le ha inserite. Per ogni post è
          possibile esprimere tutte le reaction disponibili, ma una sola volta per ciascuna
          tipologia.
        </li>

        <li>
          <strong>Relazioni sociali:</strong> È possibile visualizzare il numero dei propri follower
          e l'elenco degli utenti seguiti. Non è consentita la visualizzazione dei follower di altri
          utenti né delle liste di chi essi seguono.
        </li>
        <li>
          <strong>Home/Feed:</strong> La homepage mostra solo i post di utenti o emozioni seguiti
          esplicitamente; non sono previsti sistemi di raccomandazione o algoritmi di profilazione.
        </li>
        <li>
          <strong>Commenti/Messaggistica:</strong> Non sono presenti funzionalità di commento o
          messaggistica.
        </li>
      </ul>

      <h3>4. Inalterabilità dei Contenuti</h3>
      <p>
        Una volta inviato, un post non può essere né modificato né eliminato dall'utente. Tale
        vincolo è necessario per preservare l'integrità dei dati oggetto di ricerca. Eventuali
        rimozioni sono possibili solo a seguito di interventi di moderazione.
      </p>

      <h3>5. Regole di Condotta e Moderazione</h3>
      <p>
        L'utente è l'unico responsabile dei contenuti pubblicati. È vietato l'inserimento di
        materiale che inciti all'odio, alla violenza, alla discriminazione o che violi le leggi
        vigenti. Il progetto si riserva il diritto di sospendere gli account che violino tali
        principi o che effettuino attività di spam e manomissione tecnica.
      </p>

      <h3>6. Proprietà Intellettuale</h3>
      <p>I materiali grafici utilizzati seguono licenze specifiche:</p>
      <ul>
        <li>Le icone presenti nell'app sono ottenute da <strong>SVG Repo</strong>.</li>
        <li>
          Le immagini riportano la propria fonte e sono utilizzate in quanto di pubblico dominio o
          sotto licenza che ne permette l'uso previa citazione dell'autore.
        </li>
      </ul>
      <p>
        Il logo e il pittogramma sono stati utilizzati partendo dal font <em>Vina Sans</em>, e
        modificando quest'ultimo
      </p>

      <h3>7. Comunicazioni e Notifiche</h3>
      <p>
        Il sistema non utilizza notifiche push. Gli aggiornamenti relativi all'attività dell'account
        sono consultabili esclusivamente all'interno della sezione "notifiche" dell'applicazione.
      </p>
      <p>
        Non analizzando i dati degli utenti, le notifiche saranno relative agli utenti seguiti o
        alle emozioni seguite; non saranno mostrate notifiche su contenuti potenzialmente
        consigliati, poiché non sono presenti sistemi di raccomandazione.
      </p>

      <em>Ultimo aggiornamento: 1 feb 2026</em>
    </text-paragraph>
  </action-sheet>
</template>

<style scoped lang="scss">
.fullwidth {
  width: 100%;
  display: flex;
  justify-content: start;
}
.content {
  display: flex;
  flex-direction: column;
  padding: var(--padding-32);
  gap: var(--spacing-16);
  height: auto;

  .container {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-32);

    .textboxes {
      display: flex;
      flex-direction: column;
      gap: var(--spacing-16);
    }

    .buttons {
      display: flex;
      flex-direction: column;
      gap: var(--spacing-16);
    }
  }
  .info-box {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
  }
}
</style>
