'use client';

import { useEffect, useState } from 'react';

export default function CookieConsent() {
  const [show, setShow] = useState(false);

  useEffect(() => {
    const consent = localStorage.getItem('lgpd_consent');
    if (!consent) {
      setShow(true);
    }
  }, []);

  const handleAccept = () => {
    localStorage.setItem('lgpd_consent', 'true');
    setShow(false);
    
    // Enviar consentimento para backend
    fetch('/api/me/consent', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        data_processing: true,
        marketing_emails: false,
      }),
    }).catch(console.error);
  };

  if (!show) return null;

  return (
    <div className="fixed bottom-0 left-0 right-0 bg-gray-900 text-white p-4 z-50">
      <div className="container mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        <p className="text-sm">
          Usamos cookies para melhorar sua experiência.{' '}
          <a href="/privacidade" className="underline hover:text-purple-400">
            Saiba mais
          </a>
        </p>
        <button
          onClick={handleAccept}
          className="bg-purple-600 hover:bg-purple-700 px-6 py-2 rounded-lg font-semibold whitespace-nowrap"
        >
          Aceitar todos
        </button>
      </div>
    </div>
  );
}
