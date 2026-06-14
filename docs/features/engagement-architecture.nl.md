# Engagement-architectuur

Technische documentatie voor IntraVox' engagement-systeem (reacties en comments).

**Doelgroep:** ontwikkelaars en technische beheerders

**Gerelateerde documentatie:**

- [Engagement-beheergids](../admin/engagement.md) — configuratie voor beheerders
- [Engagement-gebruikersgids](../user/engagement.md) — eindgebruikers-documentatie
- [Autorisatie-gids](../admin/authorization.md) — permissie-model

---

## Overzicht

IntraVox gebruikt **Nextcloud's native Comments-API** (`OCP\Comments\ICommentsManager`) voor alle comment- en reactie-functionaliteit. Dit levert threading, emoji-reacties, @mentions en Activity-feed-integratie out-of-the-box.

### Waarom de Nextcloud Comments-API?

| Aspect | Custom tabellen | NC Comments-API |
|--------|-----------------|------------------|
| Database-tabellen | 2 custom | 0 (gebruikt `oc_comments`) |
| PHP-entiteiten | 2 | 0 |
| PHP-mappers | 2 | 0 |
| Migratie nodig | Ja | Nee |
| Threading | Zelf bouwen | Native |
| Emoji-reacties | Zelf bouwen | Native |
| @mentions | Niet gepland | Native |
| Activity-feed | Zelf bouwen | Native |

Apps die deze aanpak gebruiken: Nextcloud Files, Deck, Announcementcenter.

---

## Datamodel

### Object-type-registratie

IntraVox registreert `intravox_page` als comment-object-type:

```php
$event->addEntityCollection('intravox_page', function ($pageId) {
    return $this->pageService->pageExists($pageId);
});
```

### Data-koppeling

```
┌─────────────────────────────────────────────────────────────────┐
│  GROUPFOLDER (bestanden)              │  DATABASE (oc_comments) │
├───────────────────────────────────────┼─────────────────────────┤
│                                       │                         │
│  /IntraVox/nl/welcome.json            │  objectType: intravox_page
│    {                                  │  objectId: page-abc-123 │
│      "uniqueId": "page-abc-123", ◄────┼──────────────────────►  │
│      "title": "Welcome",              │                         │
│      "widgets": [...]                 │  comment_id: 1          │
│    }                                  │  message: "Great!"      │
│                                       │  user_id: "john"        │
│                                       │  reactions: ['👍','❤️'] │
│                                       │                         │
└───────────────────────────────────────┴─────────────────────────┘
```

De `uniqueId` in het .json-bestand is de **sleutel** die pagina's aan comments koppelt.

### Gedrag bij pagina-operaties

| Actie | .json-bestand | Comments in DB | Resultaat |
|-------|---------------|----------------|-----------|
| Pagina bewerken | UUID blijft | Blijven | Alles intact |
| Pagina verplaatsen | UUID blijft | Blijven | Alles intact |
| Pagina hernoemen | UUID blijft | Blijven | Alles intact |
| Pagina kopiëren | Nieuwe UUID | Niet gekopieerd | Geen comments op kopie |
| Pagina verwijderen | Weg | Verweesd | Cleanup nodig |

---

## API-endpoints

### Comments

| Methode | Endpoint | Beschrijving | Permissie |
|---------|----------|--------------|-----------|
| GET | `/api/pages/{pageId}/comments` | Comments met replies ophalen | Lees-toegang |
| POST | `/api/pages/{pageId}/comments` | Comment aanmaken | Lees-toegang |
| PUT | `/api/comments/{id}` | Eigen comment bewerken | Auteur |
| DELETE | `/api/comments/{id}` | Comment verwijderen | Auteur of Admin |

### Pagina-reacties

| Methode | Endpoint | Beschrijving | Permissie |
|---------|----------|--------------|-----------|
| GET | `/api/pages/{pageId}/reactions` | Pagina-reacties ophalen | Lees-toegang |
| POST | `/api/pages/{pageId}/reactions/{emoji}` | Reactie toevoegen | Lees-toegang |
| DELETE | `/api/pages/{pageId}/reactions/{emoji}` | Reactie verwijderen | Lees-toegang |

### Comment-reacties

| Methode | Endpoint | Beschrijving | Permissie |
|---------|----------|--------------|-----------|
| GET | `/api/comments/{id}/reactions` | Comment-reacties ophalen | Lees-toegang |
| POST | `/api/comments/{id}/reactions/{emoji}` | Reactie op comment toevoegen | Lees-toegang |
| DELETE | `/api/comments/{id}/reactions/{emoji}` | Reactie verwijderen | Lees-toegang |

### Instellingen

| Methode | Endpoint | Beschrijving | Permissie |
|---------|----------|--------------|-----------|
| GET | `/api/settings/engagement` | Engagement-instellingen ophalen | Elke gebruiker |
| PUT | `/api/settings/engagement` | Instellingen bijwerken | Alleen admin |

---

## Permissie-model

| Actie | Vereist |
|-------|---------|
| Reacties/comments bekijken | Lees-toegang tot pagina |
| Emoji-reactie toevoegen | Lees-toegang tot pagina |
| Comment plaatsen | Lees-toegang tot pagina |
| Reageren op comment | Lees-toegang tot pagina |
| Eigen comment bewerken | Comment-auteur |
| Eigen comment verwijderen | Comment-auteur |
| Elke comment verwijderen | IntraVox-admin of NC-admin |

---

## Backend-componenten

### Bestanden

| Bestand | Doel |
|---------|------|
| `lib/Listener/CommentsEntityListener.php` | `intravox_page`-objectType registreren |
| `lib/Service/CommentService.php` | Wrapper rond `ICommentsManager` |
| `lib/Controller/CommentController.php` | REST-API-endpoints |

### Kern-service-methodes

```php
class CommentService {
    // Comments
    public function getComments(string $pageId, int $limit, int $offset): array;
    public function createComment(string $pageId, string $message, ?string $parentId): array;
    public function updateComment(string $commentId, string $message): array;
    public function deleteComment(string $commentId, bool $isAdmin): void;

    // Pagina-reacties
    public function getPageReactions(string $pageId): array;
    public function addPageReaction(string $pageId, string $emoji): array;
    public function removePageReaction(string $pageId, string $emoji): array;

    // Comment-reacties
    public function getCommentReactions(string $commentId): array;
    public function addCommentReaction(string $commentId, string $emoji): array;
    public function removeCommentReaction(string $commentId, string $emoji): array;
}
```

---

## Frontend-componenten

### Bestanden

| Bestand | Doel |
|---------|------|
| `src/services/CommentService.js` | API-client |
| `src/components/reactions/ReactionBar.vue` | Emoji-reacties onder pagina |
| `src/components/reactions/CommentSection.vue` | Comments-container |
| `src/components/reactions/CommentItem.vue` | Enkele comment met replies |
| `src/components/reactions/ReactionPicker.vue` | Emoji-picker |

### Component-hiërarchie

```
PageViewer.vue
└── ReactionBar.vue          (pagina-reacties)
    └── ReactionPicker.vue
└── CommentSection.vue       (comments-container)
    └── CommentItem.vue      (enkele comment)
        └── ReactionPicker.vue
        └── CommentItem.vue  (replies, 1 niveau diep)
```

---

## Opslag van instellingen

### Admin-instellingen

Opgeslagen in Nextcloud's config via `OCP\IConfig`:

| Key | Type | Default |
|-----|------|---------|
| `intravox/allowPageReactions` | bool | true |
| `intravox/allowComments` | bool | true |
| `intravox/allowCommentReactions` | bool | true |

### Pagina-instellingen

Opgeslagen in het JSON-bestand van de pagina:

```json
{
  "uniqueId": "page-abc-123",
  "title": "My Page",
  "settings": {
    "allowReactions": null,
    "allowComments": false,
    "allowCommentReactions": null
  },
  "layout": { ... }
}
```

Waarden:

- `null` — overerven van admin (default)
- `false` — geforceerd uit

Let op: pagina's kunnen features die globaal aanstaan alleen **uitschakelen**. Ze kunnen geen features inschakelen die globaal uit staan.

---

## Resolutie van instellingen

### Prioriteit-volgorde

```
Admin-instelling (master switch) → Pagina-instelling (kan alleen uitschakelen)
```

### Beslissings-flow

```
Is feature X toegestaan op deze pagina?
├── Admin-instelling uit?
│   └── Ja → Feature uit (pagina kan niet overrulen)
└── Nee (admin aan)
    └── Pagina-instelling expliciet uit?
        ├── Ja → Feature uit
        └── Nee (null/erven) → Feature aan
```

---

## UI-positie

### Onder pagina-content

```
┌─────────────────────────────────────────────────┐
│  INTRAVOX-PAGINA                                │
├─────────────────────────────────────────────────┤
│                                                 │
│  Pagina-content...                              │
│                                                 │
│  ─────────────────────────────────────────────  │
│                                                 │
│  👍 12  ❤️ 5  🎉 3        [+ Reageer]           │  ← Reactie-balk
│                                                 │
│  ─────────────────────────────────────────────  │
│                                                 │
│  💬 Comments (8)                    [Sorteer ▼] │
│                                                 │
│  ┌─────────────────────────────────────────┐   │
│  │ 📝 Schrijf een comment...               │   │  ← Input bovenaan
│  └─────────────────────────────────────────┘   │
│                                                 │
│  ┌─────────────────────────────────────────┐   │
│  │ 👤 John Doe • 2 uur geleden             │   │
│  │ Mooi artikel!                           │   │
│  │ [Reageer] [😀] 👍 3                      │   │
│  │   └─ 👤 Jane: Mee eens!                 │   │  ← 1 niveau threading
│  └─────────────────────────────────────────┘   │
│                                                 │
└─────────────────────────────────────────────────┘
```

### Waarom onder content (niet sidebar)?

| Aspect | Sidebar | Onder content |
|--------|---------|---------------|
| Zichtbaarheid | Verborgen tot klik | Direct zichtbaar |
| Engagement | Laag (extra actie) | Hoog (gewoon scrollen) |
| Context | Gescheiden van content | Verbonden met content |
| Mobile | Sidebar problematisch | Natuurlijke flow |

---

## Technische beperkingen

- **Threading**: beperkt tot 1 niveau (comment → reply, geen reply op reply)
- **Paginering**: standaard 50 comments per keer
- **Tekst-formaat**: alleen platte tekst (geen markdown)
- **@mentions**: ondersteund via NC native support
- **Activity-feed**: automatische integratie
- **Reacties**: 18 vaste emoji-opties

---

## Export/Import (toekomst)

```json
{
  "exportVersion": "1.0",
  "pages": [
    {
      "uniqueId": "page-abc-123",
      "comments": [
        {
          "userId": "john",
          "displayName": "John Doe",
          "message": "Mooi artikel!",
          "createdAt": "2024-12-01T10:00:00Z",
          "reactions": { "👍": ["jane", "anna"] },
          "replies": [...]
        }
      ],
      "pageReactions": {
        "👍": ["john", "jane"],
        "❤️": ["anna"]
      }
    }
  ]
}
```

---

## Referenties

- [Nextcloud Comments API](https://docs.nextcloud.com/server/latest/developer_manual/client_apis/WebDAV/comments.html)
- [ICommentsManager Interface](https://github.com/nextcloud/server/blob/master/lib/public/Comments/ICommentsManager.php)
- [Announcementcenter Implementation](https://github.com/nextcloud/announcementcenter/blob/master/lib/Listener/CommentsEntityListener.php)
