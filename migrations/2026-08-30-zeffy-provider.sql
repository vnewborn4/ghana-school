USE ghana_school;

ALTER TABLE sponsorships
    MODIFY payment_provider ENUM('zeffy','stripe','paypal') NOT NULL DEFAULT 'zeffy';
