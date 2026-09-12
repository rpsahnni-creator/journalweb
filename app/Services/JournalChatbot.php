<?php

namespace App\Services;

use App\Support\JournalCopy;

class JournalChatbot
{
    /**
     * @return array{reply: string, links: list<array{label: string, url: string}>, suggestions: list<string>}
     */
    public function reply(string $message, string $locale = 'en'): array
    {
        $normalized = $this->normalize($message);
        $intent = $this->matchIntent($normalized);

        return [
            'reply' => $this->answer($intent, $locale),
            'links' => $this->links($intent, $locale),
            'suggestions' => $this->suggestions($locale),
        ];
    }

    /**
     * @return list<string>
     */
    public function suggestions(string $locale = 'en'): array
    {
        return $locale === 'hi'
            ? ['पांडुलिपि कैसे जमा करें?', 'क्या कोई शुल्क है?', 'समीक्षा कैसे होती है?', 'संपर्क कैसे करें?']
            : ['How do I submit a manuscript?', 'Is there a publication fee?', 'How does peer review work?', 'How can I contact the office?'];
    }

    public function greeting(string $locale = 'en'): string
    {
        return $locale === 'hi'
            ? 'नमस्ते! मैं पत्रिका सहायक हूँ। जमा करना, शुल्क, समीक्षा, या संपर्क के बारे में पूछें।'
            : 'Hello. I am the journal assistant. Ask about submissions, fees, peer review, or contacting the office.';
    }

    private function matchIntent(string $normalized): string
    {
        $tokens = preg_split('/\s+/u', $normalized) ?: [];
        if ($normalized === '' || array_intersect($tokens, ['hello', 'hi', 'hey', 'namaste', 'namaskar', 'help', 'मदद', 'नमस्ते', 'नमस्कार']) !== []) {
            return 'greeting';
        }

        $scores = [];
        foreach ($this->intents() as $intent => $keywords) {
            $hits = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    $hits++;
                }
            }

            if ($hits > 0) {
                $scores[$intent] = $hits / count($keywords);
            }
        }

        if ($scores === []) {
            return 'fallback';
        }

        arsort($scores);

        return (string) array_key_first($scores);
    }

    /**
     * @return array<string, list<string>>
     */
    private function intents(): array
    {
        return [
            'submit' => ['submit', 'submission', 'manuscript', 'upload', 'register', 'portal', 'जमा', 'पांडुलिपि', 'प्रस्तुति', 'अपलोड', 'पंजीकरण'],
            'guidelines' => ['guideline', 'word limit', 'apa', 'abstract', 'keyword', 'template', 'docx', 'दिशानिर्देश', 'शब्द', 'टेम्पलेट', 'सार'],
            'review' => ['review', 'peer', 'double blind', 'referee', 'revision', 'समीक्षा', 'रिव्यू', 'पीयर'],
            'apc' => ['fee', 'apc', 'charge', 'payment', 'cost', 'free', 'शुल्क', 'फीस', 'मुफ्त', 'मुक्त'],
            'plagiarism' => ['plagiarism', 'similarity', 'copy', 'साहित्यिक', 'चोरी', 'प्लेजियरिज्म'],
            'contact' => ['contact', 'email', 'phone', 'office', 'complaint', 'appeal', 'संपर्क', 'ईमेल', 'शिकायत'],
            'issues' => ['issue', 'current', 'archive', 'volume', 'अंक', 'खंड', 'संग्रह'],
            'articles' => ['article', 'pdf', 'download', 'cite', 'doi', 'लेख', 'डाउनलोड', 'उद्धरण'],
            'about' => ['about', 'scope', 'aim', 'board', 'editor', 'publisher', 'परिचय', 'उद्देश्य', 'मंडल', 'प्रकाशक'],
            'ethics' => ['ethic', 'copyright', 'license', 'conflict', 'retraction', 'नीति', 'कॉपीराइट', 'लाइसेंस'],
        ];
    }

    private function answer(string $intent, string $locale): string
    {
        $email = JournalCopy::AUTHOR_GUIDELINES_EMAIL;
        $wordLimit = JournalCopy::AUTHOR_GUIDELINES_WORD_LIMIT;
        $similarity = JournalCopy::AUTHOR_GUIDELINES_SIMILARITY_THRESHOLD;

        $answers = [
            'en' => [
                'greeting' => $this->greeting('en'),
                'submit' => 'Create a free account, then use Submit Manuscript. Files must be original MS Word (.docx) work that is not under review elsewhere. You can also email '.$email.' if the portal is unavailable.',
                'guidelines' => 'Manuscripts should be '.$wordLimit.', including references, in APA 7th edition. Include a 150–250 word abstract and 4–6 keywords. A manuscript template is available on the Author Guidelines page. Similarity above '.$similarity.' is not considered.',
                'review' => JournalCopy::REVIEW_PROCESS,
                'apc' => JournalCopy::APC_POLICY,
                'plagiarism' => JournalCopy::PLAGIARISM_POLICY,
                'contact' => 'Write to the editorial office at '.$email.' or use the Contact form. Do not upload unpublished manuscripts through the contact form.',
                'issues' => 'Published issues appear under Current Issue and Previous Issues. The journal is biannual (June and December). Only published articles are listed publicly.',
                'articles' => 'Browse published articles from the Articles page. Each article page has the abstract, citation tools, and a PDF download when a file has been released.',
                'about' => JournalCopy::ABOUT_TEXT,
                'ethics' => 'The journal follows publication ethics, copyright, conflict-of-interest, and correction policies. Use the Journal menu for the full policy pages.',
                'fallback' => 'I can help with submissions, author guidelines, fees, peer review, issues, or contact details. Try one of the suggested questions, or open the matching page from the menu.',
            ],
            'hi' => [
                'greeting' => $this->greeting('hi'),
                'submit' => 'निःशुल्क खाता बनाएँ, फिर “पांडुलिपि जमा करें” का उपयोग करें। फ़ाइल मूल MS Word (.docx) होनी चाहिए और अन्यत्र समीक्षा में नहीं होनी चाहिए। पोर्टल उपलब्ध न हो तो '.$email.' पर भी भेज सकते हैं।',
                'guidelines' => 'पांडुलिपि '.$wordLimit.' (संदर्भ सहित), APA 7वीं शैली में हो। 150–250 शब्दों का सार और 4–6 कीवर्ड दें। टेम्पलेट लेखक दिशानिर्देश पृष्ठ पर है। '.$similarity.' से अधिक समानता स्वीकार नहीं की जाती।',
                'review' => 'हर प्रस्तुति पहले संपादकीय जाँच से गुजरती है, फिर कम से कम दो स्वतंत्र समीक्षकों की डबल-ब्लाइंड पीयर रिव्यू होती है। संपादक स्वीकार, संशोधन, या अस्वीकार का निर्णय लेते हैं।',
                'apc' => 'इस पत्रिका में जमा, प्रसंस्करण, या प्रकाशन का कोई शुल्क नहीं है। लेख डायमंड ओपन एक्सेस में निःशुल्क प्रकाशित होते हैं।',
                'plagiarism' => 'साहित्यिक चोरी पर शून्य सहिष्णुता है। समीक्षा से पहले समानता जाँच होती है। चोरी या दोहरी प्रकाशन पाए जाने पर पांडुलिपि अस्वीकृत हो सकती है।',
                'contact' => 'संपादकीय कार्यालय '.$email.' पर लिखें, या संपर्क फ़ॉर्म का उपयोग करें। संपर्क फ़ॉर्म पर अप्रकाशित पांडुलिपि न भेजें।',
                'issues' => 'प्रकाशित अंक वर्तमान अंक और पिछले अंक में दिखते हैं। पत्रिका द्विवार्षिक है (जून और दिसंबर)। सार्वजनिक सूची में केवल प्रकाशित लेख होते हैं।',
                'articles' => 'लेख पृष्ठ से प्रकाशित लेख देखें। प्रत्येक लेख पर सार, उद्धरण उपकरण, और उपलब्ध होने पर PDF डाउनलोड होता है।',
                'about' => 'एसआरटी जर्नल ऑफ मल्टीडिसिप्लिनरी रिसर्च एक पीयर-रिव्यूड शैक्षणिक पत्रिका है, जो एस.आर.टी. कॉलेज, धामनी (गोड्डा) द्वारा प्रकाशित होती है।',
                'ethics' => 'पत्रिका प्रकाशन नीति, कॉपीराइट, हित-संघर्ष और शुद्धि/वापसी नीतियों का पालन करती है। पूरा पाठ जर्नल मेनू में है।',
                'fallback' => 'मैं जमा करने, दिशानिर्देश, शुल्क, समीक्षा, अंक, या संपर्क में मदद कर सकता हूँ। कोई सुझाया गया प्रश्न चुनें, या मेनू से संबंधित पृष्ठ खोलें।',
            ],
        ];

        return $answers[$locale][$intent] ?? $answers['en'][$intent] ?? $answers['en']['fallback'];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function links(string $intent, string $locale): array
    {
        $map = [
            'submit' => [['label' => $locale === 'hi' ? 'पांडुलिपि जमा करें' : 'Submit manuscript', 'url' => route('submissions.create')], ['label' => $locale === 'hi' ? 'पंजीकरण' : 'Register', 'url' => route('register')]],
            'guidelines' => [['label' => $locale === 'hi' ? 'लेखक दिशानिर्देश' : 'Author guidelines', 'url' => route('author-guidelines')], ['label' => $locale === 'hi' ? 'जमा जाँच-सूची' : 'Submission checklist', 'url' => route('submission-checklist')]],
            'review' => [['label' => $locale === 'hi' ? 'समीक्षा प्रक्रिया' : 'Review process', 'url' => route('review-process')]],
            'apc' => [['label' => $locale === 'hi' ? 'शुल्क नीति' : 'APC policy', 'url' => route('article-processing-charges')]],
            'plagiarism' => [['label' => $locale === 'hi' ? 'साहित्यिक चोरी नीति' : 'Plagiarism policy', 'url' => route('plagiarism-policy')]],
            'contact' => [['label' => $locale === 'hi' ? 'संपर्क' : 'Contact', 'url' => route('contact')]],
            'issues' => [['label' => $locale === 'hi' ? 'वर्तमान अंक' : 'Current issue', 'url' => route('issues.current')]],
            'articles' => [['label' => $locale === 'hi' ? 'लेख' : 'Articles', 'url' => route('articles.index')]],
            'about' => [['label' => $locale === 'hi' ? 'परिचय' : 'About', 'url' => route('about')]],
            'ethics' => [['label' => $locale === 'hi' ? 'प्रकाशन नीति' : 'Publication ethics', 'url' => route('publication-ethics')]],
            'greeting' => [],
            'fallback' => [['label' => $locale === 'hi' ? 'संपर्क' : 'Contact', 'url' => route('contact')]],
        ];

        return $map[$intent] ?? [];
    }

    private function normalize(string $message): string
    {
        $value = mb_strtolower(trim($message));
        $value = preg_replace('/[^\p{L}\p{M}\p{N}\s]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

}
