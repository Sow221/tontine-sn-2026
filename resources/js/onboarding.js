import Shepherd from 'shepherd.js';
import 'shepherd.js/dist/css/shepherd.css';

export function initializeOnboarding(user) {
  // Check if user has completed the onboarding
  const tourCompleted = localStorage.getItem(`onboarding-${user.id}`);
  if (tourCompleted) {
    return;
  }

  const tour = new Shepherd.Tour({
    useModalOverlay: true,
    defaultStepOptions: {
      classes: 'shepherd-theme-dark',
      scrollTo: true,
    },
  });

  // Step 1: Welcome
  tour.addStep({
    id: 'welcome',
    title: '🎉 Bienvenue sur TontineSN !',
    text: 'Ce tour rapide vous montrera les fonctionnalités principales. Vous pouvez le terminer à tout moment.',
    classes: 'shepherd-theme-dark',
    buttons: [
      { action: tour.cancel, text: 'Passer' },
      { action: tour.next, text: 'Suivant' },
    ],
  });

  // Step 2: Dashboard KPIs
  if (document.querySelector('[data-tour="dashboard-kpis"]')) {
    tour.addStep({
      id: 'kpis',
      title: '📊 Vos KPIs',
      text: 'Consultez votre score de crédit, vos streaks, paiements et position actuelle dans les tontines.',
      element: '[data-tour="dashboard-kpis"]',
      placement: 'bottom',
      buttons: [
        { action: tour.back, text: 'Précédent' },
        { action: tour.next, text: 'Suivant' },
      ],
    });
  }

  // Step 3: My Tontines
  if (document.querySelector('[data-tour="my-tontines"]')) {
    tour.addStep({
      id: 'tontines',
      title: '💰 Vos Tontines',
      text: 'Accédez à la liste de toutes vos tontines actives. Vous pouvez créer, rejoindre ou gérer une tontine.',
      element: '[data-tour="my-tontines"]',
      placement: 'bottom',
      buttons: [
        { action: tour.back, text: 'Précédent' },
        { action: tour.next, text: 'Suivant' },
      ],
    });
  }

  // Step 4: Create Tontine
  if (document.querySelector('[data-tour="create-tontine"]')) {
    tour.addStep({
      id: 'create',
      title: '➕ Créer une Tontine',
      text: 'Cliquez ici pour créer une nouvelle tontine avec vos paramètres personnalisés.',
      element: '[data-tour="create-tontine"]',
      placement: 'bottom',
      buttons: [
        { action: tour.back, text: 'Précédent' },
        { action: tour.next, text: 'Suivant' },
      ],
    });
  }

  // Step 5: Chat & Notifications
  if (document.querySelector('[data-tour="chat-link"]')) {
    tour.addStep({
      id: 'chat',
      title: '💬 Communiquez',
      text: 'Discutez avec les autres membres de votre tontine via le chat de groupe.',
      element: '[data-tour="chat-link"]',
      placement: 'bottom',
      buttons: [
        { action: tour.back, text: 'Précédent' },
        { action: tour.next, text: 'Suivant' },
      ],
    });
  }

  // Step 6: Profile
  if (document.querySelector('[data-tour="profile-link"]')) {
    tour.addStep({
      id: 'profile',
      title: '👤 Votre Profil',
      text: 'Gérez votre profil, vérifiez votre KYC et consultez votre historique de transactions.',
      element: '[data-tour="profile-link"]',
      placement: 'bottom',
      buttons: [
        { action: tour.back, text: 'Précédent' },
        { action: () => {
          tour.complete();
        }, text: 'Terminer' },
      ],
    });
  }

  // Mark tour as completed
  tour.on('complete', () => {
    localStorage.setItem(`onboarding-${user.id}`, 'true');
  });

  tour.on('cancel', () => {
    localStorage.setItem(`onboarding-${user.id}`, 'true');
  });

  // Start the tour
  tour.start();
}
