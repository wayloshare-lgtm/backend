# Flutter Backend Alignment Spec

## Overview
This spec defines the complete alignment of the WayloShare backend with Flutter app requirements. It includes 150+ tasks across 15 phases to implement 100+ missing data fields, 13 new database tables, and 40+ new API endpoints.

## Quick Links
- **Tasks**: [tasks.md](tasks.md) - Complete task list with 150+ items
- **Requirements**: [requirements.md](requirements.md) - Detailed requirements and specifications
- **Design**: [design.md](design.md) - Architecture, database schema, and API design
- **Progress**: [PROGRESS.md](PROGRESS.md) - Real-time progress tracking

## Current Status
- **Overall Progress**: 0% (0/150 tasks completed)
- **Estimated Duration**: 4-6 weeks
- **Team Size**: 1-2 developers
- **Priority**: High (Flutter app depends on this)

## What's Included

### 15 Implementation Phases
1. **Phase 1**: User Profile Enhancement (13 tasks)
2. **Phase 2**: Driver Verification System (12 tasks)
3. **Phase 3**: Vehicle Management (12 tasks)
4. **Phase 4**: Bookings System (15 tasks)
5. **Phase 5**: Ratings & Reviews (13 tasks)
6. **Phase 6**: Chat & Messaging (12 tasks)
7. **Phase 7**: Saved Routes (11 tasks)
8. **Phase 8**: Notifications & FCM (10 tasks)
9. **Phase 9**: Location Tracking (13 tasks)
10. **Phase 10**: Payment Methods (10 tasks)
11. **Phase 11**: Ride Offering (14 tasks)
12. **Phase 12**: Enhanced Profile Fields (10 tasks)
13. **Phase 13**: Data Validation & Security (10 tasks)
14. **Phase 14**: API Documentation & Testing (8 tasks)
15. **Phase 15**: Database Optimization (10 tasks)

### Key Deliverables
- ✅ 13 new database tables
- ✅ 40+ new API endpoints
- ✅ 100+ new data fields
- ✅ Complete data validation
- ✅ Security hardening
- ✅ API documentation
- ✅ Postman collection
- ✅ Unit & feature tests

## Database Tables (13 Total)

### Extended Tables
- users (8 new fields)
- driver_profiles (5 new fields)
- rides (9 new fields)

### New Tables
- driver_verifications
- vehicles
- bookings
- reviews
- chats
- messages
- saved_routes
- fcm_tokens
- ride_locations
- payment_methods

## API Endpoints (40+ Total)

### By Category
- **Authentication**: 4 endpoints
- **User Profile**: 8 endpoints
- **Driver Verification**: 6 endpoints
- **Vehicles**: 6 endpoints
- **Rides**: 12 endpoints
- **Bookings**: 6 endpoints
- **Reviews**: 4 endpoints
- **Chat**: 6 endpoints
- **Saved Routes**: 5 endpoints
- **Notifications**: 4 endpoints
- **Location**: 3 endpoints
- **Payment**: 5 endpoints

## Data Fields (100+ Total)

### By Category
- User Profile: 13 fields
- Driver Verification: 12 fields
- Vehicle Management: 8 fields
- Bookings: 10 fields
- Ratings & Reviews: 8 fields
- Chat & Messaging: 6 fields
- Saved Routes: 5 fields
- Notifications: 5 fields
- Location Tracking: 9 fields
- Payment Methods: 5 fields
- Ride Offering: 14 fields
- Enhanced Profile: 8 fields

## How to Use This Spec

### 1. Review the Requirements
Start with [requirements.md](requirements.md) to understand what needs to be built.

### 2. Review the Design
Check [design.md](design.md) for architecture, database schema, and API design.

### 3. Track Progress
Use [PROGRESS.md](PROGRESS.md) to track completion of each task.

### 4. Execute Tasks
Follow [tasks.md](tasks.md) to implement each task in order.

### 5. Update Progress
After completing each task, mark it as done in PROGRESS.md and commit to GitHub.

## Weekly Milestones

| Week | Phases | Tasks | Target |
|------|--------|-------|--------|
| 1 | 1-2 | 25 | User Profile & Driver Verification |
| 2 | 3-4 | 27 | Vehicle & Booking System |
| 3 | 5-6 | 25 | Reviews & Chat |
| 4 | 7-8 | 21 | Routes & Notifications |
| 5 | 9-10 | 23 | Location & Payment |
| 6 | 11-15 | 52 | Ride Offering & Optimization |

## Success Criteria

- [ ] All 150+ tasks completed
- [ ] All 40+ endpoints implemented
- [ ] All 13 database tables created
- [ ] 100+ data fields supported
- [ ] All validations implemented
- [ ] Security hardening complete
- [ ] API documentation complete
- [ ] Postman collection updated
- [ ] Unit tests passing
- [ ] Feature tests passing
- [ ] Integration tests passing
- [ ] Performance targets met
- [ ] Zero critical bugs
- [ ] Flutter app integration successful

## Key Features

### User Profile Enhancement
- Extended user profile with 8 new fields
- Profile photo uploads
- Onboarding tracking
- User preferences (driver/passenger/both)

### Driver Verification
- Document uploads (DL, RC, vehicle photos)
- Verification status tracking
- Insurance information
- KYC status endpoint

### Vehicle Management
- Multiple vehicles per driver
- Vehicle types with auto-determined seating
- Vehicle photos
- Default vehicle selection

### Bookings System
- Passenger booking management
- Special requests and luggage info
- Accessibility requirements
- Booking status tracking

### Ratings & Reviews
- 1-5 star ratings
- Category-based ratings
- Review photos
- Separate driver and passenger ratings

### Chat & Messaging
- Real-time messaging
- Text, image, and location messages
- Message read status
- Attachments support

### Saved Routes
- Frequently used routes
- Pinned favorites
- Last used tracking
- Quick access

### Notifications
- FCM token management
- Device type tracking
- Notification preferences
- Multiple notification types

### Location Tracking
- Real-time driver location
- GPS accuracy and speed
- Location history
- Ride tracking

### Payment Methods
- Multiple payment types (card, wallet, UPI)
- Encrypted payment details
- Default payment method
- Payment method management

## Security Measures
- Encrypt payment data
- Validate all file uploads
- Implement rate limiting
- Use HTTPS only
- Proper CORS configuration
- Input sanitization
- Audit logging
- Prepared statements

## Performance Targets
- API response time: <200ms
- Database query time: <50ms
- Queue processing time: <5s
- Error rate: <0.1%
- Ride acceptance success rate: >99%

## Dependencies
- Laravel 11 framework
- MySQL 8.0+ database
- Redis cache
- Firebase Admin SDK
- Sanctum authentication
- File storage system
- Queue system

## Getting Started

### Step 1: Review Spec
Read through requirements.md and design.md to understand the full scope.

### Step 2: Start Phase 1
Begin with Phase 1 (User Profile Enhancement) tasks.

### Step 3: Track Progress
Update PROGRESS.md as you complete each task.

### Step 4: Commit Changes
Commit to GitHub after each phase completion.

### Step 5: Move to Next Phase
After Phase 1 is complete, move to Phase 2.

## Support & Questions

For questions or clarifications:
1. Check the relevant document (requirements.md, design.md, tasks.md)
2. Review the PROGRESS.md for similar completed tasks
3. Check the API design in design.md
4. Refer to the database schema in design.md

## Timeline

- **Start Date**: 2026-03-14
- **Estimated End Date**: 2026-04-25 (6 weeks)
- **Sprint Duration**: 1 week per 2-3 phases
- **Review Frequency**: Weekly

## Next Steps

1. ✅ Spec created and documented
2. ⏳ Review spec with team
3. ⏳ Approve requirements and design
4. ⏳ Start Phase 1 implementation
5. ⏳ Complete all 15 phases
6. ⏳ Deploy to production
7. ⏳ Integrate with Flutter app

---

**Spec Version**: 1.0  
**Created**: 2026-03-14  
**Status**: Ready for Implementation  
**Last Updated**: 2026-03-14

**Ready to start? Begin with Phase 1 in tasks.md!**
