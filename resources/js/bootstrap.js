import axios from 'axios';
import { initializeOnboarding } from './onboarding';

window.axios = axios;
window.initializeOnboarding = initializeOnboarding;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ── Service Worker Registration & FCM Support ──────────────────
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/serviceworker.js', { scope: '/' })
      .then((registration) => {
        console.log('✅ Service Worker enregistré:', registration.scope);
        
        // Initialiser FCM après enregistrement du service worker
        initializeFCM(registration);
      })
      .catch((error) => {
        console.error('❌ Erreur Service Worker:', error);
      });
  });
}

// ── FCM Initialization ─────────────────────────────────────────
async function initializeFCM(swRegistration) {
  const fcmToken = document.querySelector('meta[name="fcm-token"]');
  const publicKey = document.querySelector('meta[name="fcm-public-key"]');

  if (!publicKey?.content) {
    console.warn('⚠️ FCM public key not found');
    return;
  }

  try {
    // Demander la permission de notification
    if (Notification.permission === 'denied') {
      console.warn('⚠️ Notifications bloquées par l\'utilisateur');
      return;
    }

    if (Notification.permission === 'default') {
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        console.warn('⚠️ Utilisateur a refusé les notifications');
        return;
      }
    }

    // S'abonner aux push notifications
    const subscription = await swRegistration.pushManager.getSubscription();
    if (!subscription) {
      const newSubscription = await swRegistration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: publicKey.content,
      });
      console.log('✅ Push notifications activées');
      
      // Enregistrer le token FCM sur le serveur
      await registerFCMToken(newSubscription);
    }
  } catch (error) {
    console.error('❌ Erreur FCM:', error);
  }
}

// ── Register FCM Token with Server ─────────────────────────────
async function registerFCMToken(subscription) {
  try {
    const response = await axios.post('/api/fcm-token', {
      endpoint: subscription.endpoint,
      p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
      auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth')))),
    });

    if (response.status === 200 || response.status === 201) {
      console.log('✅ Token FCM enregistré');
    }
  } catch (error) {
    console.error('❌ Erreur lors de l\'enregistrement du token FCM:', error);
  }
}

